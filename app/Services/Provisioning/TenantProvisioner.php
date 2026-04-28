<?php

namespace App\Services\Provisioning;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * End-to-end tenant server provisioner.
 *
 * Each public method represents one step of the 22-step pipeline (see plan
 * at /home/forge/.claude/plans/compressed-skipping-shannon.md). Every step
 * is idempotent: it checks whether its artifact already exists (via GET)
 * before creating, so a failed provision can be re-dispatched and will
 * skip completed steps.
 *
 * The orchestrating job (App\Jobs\ProvisionTenantServer) calls these in
 * order and handles the polling steps (createServer, SSL, recipes) by
 * re-queueing itself.
 */
class TenantProvisioner
{
    public function __construct(
        private ForgeClient $forge,
        private CloudflareClient $cloudflare,
        private TenantSubscriptionSyncer $subscriptionSyncer,
        private TenantBilling $billing,
    ) {}

    // ─────────────────────────────────────────────────────────────
    // Step 1-2: validate and reserve the Tenant row
    // ─────────────────────────────────────────────────────────────

    public function validateAndReserve(Tenant $tenant): void
    {
        $subdomain = Str::lower($tenant->subdomain);

        if (! preg_match('/^[a-z0-9][a-z0-9-]{1,39}$/', $subdomain)) {
            throw new RuntimeException("Invalid subdomain: {$subdomain}");
        }

        if (Tenant::isReservedSubdomain($subdomain)) {
            throw new RuntimeException("Reserved subdomain: {$subdomain}");
        }

        // Uniqueness was enforced at DB level, but double-check in case of
        // concurrent signups racing.
        $other = Tenant::where('subdomain', $subdomain)->where('id', '!=', $tenant->id)->first();
        if ($other) {
            throw new RuntimeException("Subdomain already taken: {$subdomain}");
        }

        // Generate all the secrets we'll need downstream. Doing it here
        // (once) means retries reuse the same values — tenants don't get
        // multiple APP_KEYs if we crash mid-pipeline.
        if (! $tenant->app_key) {
            $tenant->app_key = 'base64:'.base64_encode(random_bytes(32));
        }
        if (! $tenant->reverb_app_id) {
            $tenant->reverb_app_id = (string) random_int(100000, 999999);
        }
        if (! $tenant->reverb_app_key) {
            $tenant->reverb_app_key = Str::random(32);
        }
        if (! $tenant->reverb_app_secret) {
            $tenant->reverb_app_secret = Str::random(40);
        }
        if (! $tenant->stats_hmac_key) {
            $tenant->stats_hmac_key = bin2hex(random_bytes(32));
        }
        if (! $tenant->db_password) {
            $tenant->db_password = Str::random(24);
        }

        $tenant->status = TenantStatus::Provisioning;
        $tenant->recordStep(2, 'reserved + secrets generated');
    }

    // ─────────────────────────────────────────────────────────────
    // Step 3: Forge createServer — async, caller polls
    // ─────────────────────────────────────────────────────────────

    public function ensureServer(Tenant $tenant): void
    {
        if ($tenant->forge_server_id && $this->forge->serverIsReady($tenant->forge_server_id)) {
            // Already ready — capture IP if missing and move on.
            $server = $this->forge->getServer($tenant->forge_server_id);
            if (! $tenant->server_ip) {
                $tenant->server_ip = $server->ipAddress;
                $tenant->save();
            }
            $tenant->recordStep(3, 'server already ready', ['server_id' => $tenant->forge_server_id]);

            return;
        }

        if ($tenant->forge_server_id) {
            // Server exists but not yet ready — caller (job) should re-queue.
            $tenant->recordStep(3, 'server provisioning in progress — will poll');

            return;
        }

        // Fresh create.
        $server = $this->forge->createServer(
            name: "qsdr-{$tenant->subdomain}",
            options: [
                'size' => $tenant->tier->serverSize(),
            ],
        );

        $tenant->forge_server_id = $server->id;
        $tenant->server_ip = $server->ipAddress;
        $tenant->recordStep(3, 'server created, waiting for provisioning', [
            'server_id' => $server->id,
            'size' => $tenant->tier->serverSize(),
        ]);
    }

    public function serverIsReady(Tenant $tenant): bool
    {
        if (! $tenant->forge_server_id) {
            return false;
        }

        $ready = $this->forge->serverIsReady($tenant->forge_server_id);

        if ($ready && ! $tenant->server_ip) {
            $server = $this->forge->getServer($tenant->forge_server_id);
            $tenant->server_ip = $server->ipAddress;
            $tenant->save();
        }

        return $ready;
    }

    // ─────────────────────────────────────────────────────────────
    // Step 4: Cloudflare DNS
    // ─────────────────────────────────────────────────────────────

    public function ensureDnsRecords(Tenant $tenant): void
    {
        if (! $tenant->server_ip) {
            throw new RuntimeException('Cannot create DNS records without server IP');
        }

        $mainId = $this->cloudflare->upsertARecord($tenant->primaryHost(), $tenant->server_ip);
        $wsId = $this->cloudflare->upsertARecord($tenant->websocketHost(), $tenant->server_ip);

        $tenant->cloudflare_record_id = $mainId;
        $tenant->cloudflare_ws_record_id = $wsId;
        $tenant->recordStep(4, 'DNS records upserted', [
            'main' => $mainId,
            'ws' => $wsId,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Step 5: primary site
    // ─────────────────────────────────────────────────────────────

    public function ensurePrimarySite(Tenant $tenant): void
    {
        if (! $tenant->forge_server_id) {
            throw new RuntimeException('No server for primary site');
        }

        $domain = $tenant->primaryHost();
        $site = $this->forge->findSiteByDomain($tenant->forge_server_id, $domain);

        if ($site === null) {
            $site = $this->forge->createSite(
                $tenant->forge_server_id,
                $domain,
                ['project_type' => 'php', 'directory' => '/public'],
            );
        }

        $tenant->forge_site_id = $site->id;
        $tenant->recordStep(5, 'primary site ensured', ['site_id' => $site->id, 'domain' => $domain]);
    }

    // ─────────────────────────────────────────────────────────────
    // Step 6: git repo
    // ─────────────────────────────────────────────────────────────

    public function ensureGitRepo(Tenant $tenant, string $repo = 'stanfomini/quicksdrsaaspull', string $branch = 'main'): void
    {
        if (! $tenant->forge_server_id || ! $tenant->forge_site_id) {
            throw new RuntimeException('No server/site for git install');
        }

        // Forge's API will 4xx if a repo is already installed. Check first.
        $site = $this->forge->getSite($tenant->forge_server_id, $tenant->forge_site_id);
        if ($site->repository) {
            $tenant->recordStep(6, 'git repo already installed', ['repo' => $site->repository]);

            return;
        }

        $this->forge->installGitRepository($tenant->forge_server_id, $tenant->forge_site_id, $repo, $branch);
        $tenant->recordStep(6, 'git repo installed', ['repo' => $repo, 'branch' => $branch]);
    }

    // ─────────────────────────────────────────────────────────────
    // Step 7: database
    // ─────────────────────────────────────────────────────────────

    public function ensureDatabase(Tenant $tenant): void
    {
        if (! $tenant->forge_server_id) {
            throw new RuntimeException('No server for database');
        }

        $dbName = "tenant_{$tenant->subdomain}";
        $existing = $this->forge->findDatabaseByName($tenant->forge_server_id, $dbName);

        if ($existing) {
            $tenant->forge_database_id = $existing->id;
            $tenant->recordStep(7, 'database already exists', ['db_id' => $existing->id, 'name' => $dbName]);

            return;
        }

        $db = $this->forge->createDatabase(
            $tenant->forge_server_id,
            $dbName,
            "tenant_{$tenant->subdomain}",
            $tenant->db_password,
        );

        $tenant->forge_database_id = $db->id;
        $tenant->recordStep(7, 'database created', ['db_id' => $db->id, 'name' => $dbName]);
    }

    // ─────────────────────────────────────────────────────────────
    // Step 8: env file
    // ─────────────────────────────────────────────────────────────

    public function writeEnvFile(Tenant $tenant): void
    {
        if (! $tenant->forge_server_id || ! $tenant->forge_site_id) {
            throw new RuntimeException('No server/site for env write');
        }

        $env = $this->buildEnvFileContents($tenant);
        $this->forge->updateSiteEnvironment($tenant->forge_server_id, $tenant->forge_site_id, $env);
        $tenant->recordStep(8, 'env file written');
    }

    private function buildEnvFileContents(Tenant $tenant): string
    {
        $lines = [
            'APP_NAME="QuickSDR"',
            'APP_ENV=production',
            'APP_KEY='.$tenant->app_key,
            'APP_DEBUG=false',
            'APP_URL='.$tenant->appUrl(),
            'APP_MODE=tenant',
            'ORCHESTRATOR_URL='.rtrim(config('app.url', 'https://quicksdr.com'), '/'),
            'STATS_HMAC_KEY='.$tenant->stats_hmac_key,
            '',
            ...$this->subscriptionEnvLines($tenant),
            '',
            'LOG_CHANNEL=stack',
            'LOG_LEVEL=info',
            '',
            'DB_CONNECTION=pgsql',
            'DB_HOST=127.0.0.1',
            'DB_PORT=5432',
            'DB_DATABASE=tenant_'.$tenant->subdomain,
            'DB_USERNAME=tenant_'.$tenant->subdomain,
            'DB_PASSWORD='.$tenant->db_password,
            '',
            'BROADCAST_CONNECTION=reverb',
            'QUEUE_CONNECTION=database',
            'CACHE_STORE=database',
            'SESSION_DRIVER=database',
            'SESSION_LIFETIME=120',
            '',
            'REVERB_APP_ID='.$tenant->reverb_app_id,
            'REVERB_APP_KEY='.$tenant->reverb_app_key,
            'REVERB_APP_SECRET='.$tenant->reverb_app_secret,
            'REVERB_HOST='.$tenant->websocketHost(),
            'REVERB_PORT=443',
            'REVERB_SCHEME=https',
            'REVERB_SERVER_PORT=8080',
            '',
            'VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"',
            'VITE_REVERB_HOST="${REVERB_HOST}"',
            'VITE_REVERB_PORT="${REVERB_PORT}"',
            'VITE_REVERB_SCHEME="${REVERB_SCHEME}"',
            '',
            '# Tenant fills these in from /claimstaker/settings after first login:',
            '# TWILIO_ACCOUNT_SID=',
            '# TWILIO_AUTH_TOKEN=',
            '# XAI_API_KEY= (or other AI provider)',
            '',
            ...$this->mailEnvLines($tenant),
            '',
            'PYTHON_ENABLED=true',
            'PYTHON_URL=http://127.0.0.1:8100',
            'PYTHON_TIMEOUT=60',
        ];

        return implode("\n", $lines)."\n";
    }

    /**
     * Mail + Mailgun config lines copied from the orchestrator's own config
     * so each tenant server can send its own mail (password resets, queue
     * notifications, etc.) through the same Mailgun pipe. If a key is null
     * on the orchestrator, we skip it rather than writing an empty value.
     *
     * FROM address defaults to the orchestrator's configured sender so every
     * tenant's outbound mail appears to come from the same branded address.
     *
     * @return array<int, string>
     */
    public function mailEnvLines(Tenant $tenant): array
    {
        $pairs = $this->mailEnvPairs($tenant);

        $lines = [];
        foreach ($pairs as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            // Quote values that contain spaces, or any of the shell-sensitive
            // characters Forge's .env parser cares about. Leave simple atoms
            // unquoted to match the existing file style.
            $needsQuotes = (bool) preg_match('/[\s"\'#$@]/', (string) $value);
            $lines[] = $needsQuotes
                ? $key.'="'.str_replace('"', '\\"', (string) $value).'"'
                : $key.'='.$value;
        }

        return $lines;
    }

    /**
     * Flat map of the mail env keys we want to propagate from orchestrator
     * → tenant. Null / empty values are skipped by mailEnvLines(). Public so
     * the backfill command can reuse it.
     *
     * @return array<string, string|null>
     */
    public function mailEnvPairs(Tenant $tenant): array
    {
        return [
            'MAIL_MAILER' => config('mail.default'),
            'MAIL_SCHEME' => config('mail.mailers.smtp.scheme'),
            'MAIL_HOST' => config('mail.mailers.smtp.host'),
            'MAIL_PORT' => (string) (config('mail.mailers.smtp.port') ?? ''),
            'MAIL_USERNAME' => config('mail.mailers.smtp.username'),
            'MAIL_PASSWORD' => config('mail.mailers.smtp.password'),
            'MAIL_ENCRYPTION' => config('mail.mailers.smtp.encryption'),
            'MAIL_FROM_ADDRESS' => config('mail.from.address'),
            'MAIL_FROM_NAME' => config('mail.from.name', 'QuickSDR'),
            'MAILGUN_DOMAIN' => config('services.mailgun.domain'),
            'MAILGUN_SECRET' => config('services.mailgun.secret'),
            'MAILGUN_ENDPOINT' => config('services.mailgun.endpoint'),
        ];
    }

    /**
     * Tier + Stripe subscription snapshot that the tenant reads via
     * config/subscription.php. Empty values for fields we can't resolve
     * (e.g. no stripe_subscription_id yet) so the tenant shows a graceful
     * "not yet available" state rather than error.
     *
     * @return array<int, string>
     */
    private function subscriptionEnvLines(Tenant $tenant): array
    {
        $values = $this->subscriptionSyncer->valuesForTenant($tenant)
            ?? array_fill_keys(TenantSubscriptionSyncer::KEYS, '');

        $lines = [];
        foreach ($values as $key => $value) {
            $lines[] = $key.'='.$value;
        }

        return $lines;
    }

    // ─────────────────────────────────────────────────────────────
    // Step 9-10: SSL
    // ─────────────────────────────────────────────────────────────

    public function requestPrimarySsl(Tenant $tenant): void
    {
        if ($this->forge->activeCertificateExists($tenant->forge_server_id, $tenant->forge_site_id)) {
            $tenant->recordStep(9, 'primary SSL already active');

            return;
        }

        $this->forge->obtainLetsEncryptCertificate(
            $tenant->forge_server_id,
            $tenant->forge_site_id,
            [$tenant->primaryHost()],
        );
        $tenant->recordStep(9, 'primary SSL requested');
    }

    public function ensureWebsocketSite(Tenant $tenant): void
    {
        if (! $tenant->forge_server_id) {
            throw new RuntimeException('No server for ws site');
        }

        $wsDomain = $tenant->websocketHost();
        $site = $this->forge->findSiteByDomain($tenant->forge_server_id, $wsDomain);

        if ($site === null) {
            $site = $this->forge->createSite(
                $tenant->forge_server_id,
                $wsDomain,
                ['project_type' => 'php', 'directory' => '/public'],
            );
        }

        $tenant->forge_ws_site_id = $site->id;

        if (! $this->forge->activeCertificateExists($tenant->forge_server_id, $site->id)) {
            $this->forge->obtainLetsEncryptCertificate(
                $tenant->forge_server_id,
                $site->id,
                [$wsDomain],
            );
        }

        $tenant->recordStep(10, 'ws site + SSL ensured', ['ws_site_id' => $site->id]);
    }

    // ─────────────────────────────────────────────────────────────
    // Step 11: deploy script + deploy
    // ─────────────────────────────────────────────────────────────

    public function ensureDeployScript(Tenant $tenant): void
    {
        $script = <<<'BASH'
        cd $FORGE_SITE_PATH

        git pull origin $FORGE_SITE_BRANCH

        $FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

        ( flock -w 10 9 || exit 1
            echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock

        if [ -f artisan ]; then
            $FORGE_PHP artisan migrate --force
            $FORGE_PHP artisan config:cache
            $FORGE_PHP artisan route:cache
            $FORGE_PHP artisan view:cache

            if [ -f package.json ]; then
                npm ci
                npm run build
            fi
        fi
        BASH;

        $this->forge->updateDeployScript($tenant->forge_server_id, $tenant->forge_site_id, $script);
        $this->forge->deploySite($tenant->forge_server_id, $tenant->forge_site_id);
        $tenant->recordStep(11, 'deploy script set + site deployed');
    }

    // ─────────────────────────────────────────────────────────────
    // Steps 12-13: daemons
    // ─────────────────────────────────────────────────────────────

    public function ensureDaemons(Tenant $tenant): void
    {
        // Forge deploys sites to /home/forge/{host}/ by default. The '/current'
        // symlink only exists when zero-downtime deploy is enabled (which we
        // don't turn on for tenants). Pointing daemons at /current causes
        // supervisor to fail with 'couldn't chdir ... ENOENT'.
        $sitePath = "/home/forge/{$tenant->primaryHost()}";

        // Queue worker
        $queueCmd = "php {$sitePath}/artisan queue:work --queue=default --timeout=120 --sleep=10 --tries=3";
        if (! $this->forge->findDaemonByCommand($tenant->forge_server_id, 'queue:work')) {
            $this->forge->createDaemon($tenant->forge_server_id, $queueCmd, 'forge', $sitePath);
        }

        // Reverb
        $reverbCmd = "php {$sitePath}/artisan reverb:start --no-interaction --port=8080";
        if (! $this->forge->findDaemonByCommand($tenant->forge_server_id, 'reverb:start')) {
            $this->forge->createDaemon($tenant->forge_server_id, $reverbCmd, 'forge', $sitePath);
        }

        $tenant->recordStep(13, 'queue + Reverb daemons ensured');
    }

    // ─────────────────────────────────────────────────────────────
    // Steps 14-15: recipes (Python + Reverb nginx/ssl systemd)
    // ─────────────────────────────────────────────────────────────

    public function ensureRecipe(string $name, string $script): int
    {
        $existing = $this->forge->findRecipeByName($name);
        if ($existing) {
            return $existing->id;
        }

        $recipe = $this->forge->createRecipe($name, $script);

        return $recipe->id;
    }

    public function runPythonRecipe(Tenant $tenant): void
    {
        $script = file_get_contents(base_path('vendor/stanfomini/quick-claimstaker/forge-recipes/install-python.sh'));
        $recipeId = $this->ensureRecipe('claimstaker-install-python', $script);
        $this->forge->runRecipe($recipeId, [$tenant->forge_server_id]);

        // install-python.sh creates the venv INSIDE the package path:
        //   /home/forge/{host}/vendor/stanfomini/quick-claimstaker/python/venv/
        // It does NOT create /home/forge/claimstaker-python — that path was
        // a leftover assumption from an older recipe version.
        $pyPackagePath = "/home/forge/{$tenant->primaryHost()}/vendor/stanfomini/quick-claimstaker/python";
        $pythonDaemon = "{$pyPackagePath}/venv/bin/uvicorn api.main:app --host 127.0.0.1 --port 8100";

        if (! $this->forge->findDaemonByCommand($tenant->forge_server_id, 'uvicorn api.main')) {
            $this->forge->createDaemon(
                $tenant->forge_server_id,
                $pythonDaemon,
                'forge',
                $pyPackagePath,
            );
        }

        $tenant->recordStep(14, 'Python recipe kicked + daemon ensured');
    }

    /**
     * Rewrite the ws.{subdomain}.quicksdr.com nginx vhost so it reverse-proxies
     * to the Reverb daemon on 127.0.0.1:8080 instead of Forge's default
     * Laravel template (which tries to execute PHP for every request and
     * returns "No input file specified." for /apps/{app_id}/events — the
     * path Laravel's Pusher driver POSTs to when broadcasting).
     *
     * Replaces the broken recipe-based approach: the setup-reverb.sh recipe
     * expects WS_HOSTNAME/APP_PATH/ADMIN_EMAIL bash vars to be set at run
     * time, which Forge recipes don't support. The recipe would die at
     * step 0 for every tenant, and Forge's fire-and-forget recipe runner
     * never surfaced the failure — so step 15 looked green while broadcasting
     * was silently broken.
     *
     * We keep Forge's SSL cert wiring and config markers intact; only the
     * `location /` block and the `location ~ \.php$` block are rewritten.
     * Forge runs `nginx -t` before accepting the update, so a bad template
     * is rejected without affecting the running site.
     */
    public function runReverbRecipe(Tenant $tenant): void
    {
        if (! $tenant->forge_server_id || ! $tenant->forge_ws_site_id) {
            throw new RuntimeException('No server/ws-site for Reverb nginx rewrite');
        }

        $current = $this->forge->siteNginxFile(
            $tenant->forge_server_id,
            $tenant->forge_ws_site_id,
        );

        $proxyLocation = <<<'NGINX'
location / {
        proxy_http_version 1.1;
        proxy_set_header Host $http_host;
        proxy_set_header Scheme $scheme;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_pass http://127.0.0.1:8080;
        proxy_read_timeout 60s;
    }
NGINX;

        // Replace the default try_files location / with the proxy block.
        $new = preg_replace(
            '#location / \{\s*try_files[^}]*\}#',
            $proxyLocation,
            $current,
            1,
            $locCount,
        );

        if ($locCount !== 1) {
            // If the vhost has already been rewritten on a re-provision we'll
            // find our proxy_pass already in place — idempotent no-op.
            if (str_contains($current, 'proxy_pass http://127.0.0.1:8080')) {
                $tenant->recordStep(15, 'Reverb nginx already configured — skipping');

                return;
            }
            throw new RuntimeException('Unexpected ws-site nginx shape: could not locate the try_files block to replace');
        }

        // Remove the PHP-FPM location block — this vhost doesn't serve PHP.
        $new = preg_replace('#location ~ \\\\\.php\\$ \{[^}]*\}\s*#', '', $new, 1);

        $this->forge->updateSiteNginxFile(
            $tenant->forge_server_id,
            $tenant->forge_ws_site_id,
            $new,
        );

        $tenant->recordStep(15, 'Reverb nginx vhost rewritten to proxy 127.0.0.1:8080', [
            'ws_site_id' => $tenant->forge_ws_site_id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Step 16: scheduler
    // ─────────────────────────────────────────────────────────────

    public function enableScheduler(Tenant $tenant): void
    {
        $sitePath = "/home/forge/{$tenant->primaryHost()}/current";
        $this->forge->enableScheduler(
            $tenant->forge_server_id,
            "php {$sitePath}/artisan schedule:run",
        );
        $tenant->recordStep(16, 'scheduler enabled');
    }

    // ─────────────────────────────────────────────────────────────
    // Step 17: seed admin (via SSH recipe)
    // ─────────────────────────────────────────────────────────────

    public function seedAdmin(Tenant $tenant): void
    {
        if (! $tenant->forge_server_id || ! $tenant->forge_site_id) {
            throw new RuntimeException('No server/site to seed admin on');
        }

        // create-admin upsers the User + Agent(role=admin) directly and prints
        // a password-reset URL to stdout (RESET_URL=https://...) which the
        // orchestrator captures in step 18 and emails to the admin. We don't
        // ask the tenant to send the email itself because MAIL_MAILER=log on
        // fresh tenants — the orchestrator has the real SMTP credentials.
        $email = escapeshellarg($tenant->admin_email);
        $name = escapeshellarg($tenant->admin_name ?: $tenant->admin_email);
        $business = escapeshellarg($tenant->business_name ?: ($tenant->admin_name ?: $tenant->admin_email));
        $cmd = "php artisan claimstaker:create-admin {$email} {$name} --business={$business} --with-reset-url";

        $commandId = $this->forge->executeSiteCommand(
            $tenant->forge_server_id,
            $tenant->forge_site_id,
            $cmd,
        );

        $tenant->recordStep(17, 'admin create dispatched', ['command_id' => $commandId]);
    }

    public function readAdminSignupUrl(Tenant $tenant, int $commandId): ?string
    {
        $output = $this->forge->getSiteCommandOutput(
            $tenant->forge_server_id,
            $tenant->forge_site_id,
            $commandId,
        );

        if (! $output) {
            return null;
        }

        // Password-reset URL printed by claimstaker:create-admin.
        if (preg_match('#RESET_URL=(https?://\S+)#', $output, $m)) {
            return $m[1];
        }

        // Legacy fallback: old invite-admin command prints the signup URL
        // alone on its last line. Retained so old tenants mid-provisioning
        // still complete gracefully after the orchestrator upgrade.
        if (preg_match('#(https?://[^\s]+/claimstaker/admin-signup/[a-zA-Z0-9]+)#', $output, $m)) {
            return $m[1];
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────
    // Step 18: credentials email (dispatched as queued Mailable by the job)
    // ─────────────────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────
    // Step 19: PartnerStack conversion
    // ─────────────────────────────────────────────────────────────

    public function notifyPartnerstack(Tenant $tenant): void
    {
        if (! $tenant->partnerstack_partner_key) {
            $tenant->recordStep(19, 'no PartnerStack key — skipped');

            return;
        }

        $token = config('services.partnerstack.token');
        if (! $token) {
            Log::warning('PartnerStack token not configured; skipping conversion post', [
                'tenant_id' => $tenant->id,
            ]);
            $tenant->recordStep(19, 'PartnerStack token missing — skipped');

            return;
        }

        $amountCents = $tenant->tier->monthlyPriceCents() + $tenant->tier->setupFeeCents();

        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(10)
            ->post('https://api.partnerstack.com/v2/conversions', [
                'partner_key' => $tenant->partnerstack_partner_key,
                'customer_key' => (string) $tenant->id,
                'external_key' => (string) $tenant->id,
                'amount' => $amountCents / 100,
                'currency' => 'USD',
                'type' => 'subscription',
            ]);

        if ($response->successful()) {
            $tenant->recordStep(19, 'PartnerStack conversion posted');
        } else {
            Log::warning('PartnerStack conversion failed', [
                'tenant_id' => $tenant->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $tenant->recordStep(19, "PartnerStack returned {$response->status()}");
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Steps 20-22: broadcast, mark ready, health check
    // ─────────────────────────────────────────────────────────────

    public function markReady(Tenant $tenant): void
    {
        $tenant->status = TenantStatus::Ready;
        $tenant->provisioned_at = now();
        $tenant->recordStep(21, 'ready');
    }

    /**
     * Destroy the tenant's Forge server + DNS records, cancel the Stripe
     * subscription, and mark any open invoices uncollectible. Invoked from
     * DestroyTenantServer job (after the 7-day export-only grace period)
     * and from the admin "Destroy server" button. Each step is wrapped so
     * a failure in one doesn't strand the others — destroy is best-effort.
     */
    public function destroy(Tenant $tenant): void
    {
        if ($tenant->forge_server_id) {
            try {
                $this->forge->destroyServer($tenant->forge_server_id);
            } catch (\Throwable $e) {
                Log::warning('Forge destroyServer failed (continuing)', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach ([$tenant->cloudflare_record_id, $tenant->cloudflare_ws_record_id] as $recordId) {
            if ($recordId) {
                try {
                    $this->cloudflare->deleteRecord($recordId);
                } catch (\Throwable $e) {
                    Log::warning('Cloudflare deleteRecord failed (continuing)', [
                        'tenant_id' => $tenant->id,
                        'record_id' => $recordId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if ($tenant->stripe_subscription_id) {
            try {
                $this->billing->cancelSubscription($tenant);
                Log::info('TenantProvisioner.destroy: Stripe subscription cancelled', [
                    'tenant_id' => $tenant->id,
                    'subscription' => $tenant->stripe_subscription_id,
                ]);
            } catch (\Throwable $e) {
                Log::warning('TenantProvisioner.destroy: Stripe cancel failed (continuing)', [
                    'tenant_id' => $tenant->id,
                    'subscription' => $tenant->stripe_subscription_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($tenant->stripe_customer_id) {
            try {
                $marked = $this->billing->markOpenInvoicesUncollectible($tenant);
                if ($marked > 0) {
                    Log::info('TenantProvisioner.destroy: open invoices marked uncollectible', [
                        'tenant_id' => $tenant->id,
                        'count' => $marked,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('TenantProvisioner.destroy: invoice cleanup failed (continuing)', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $tenant->status = TenantStatus::Destroyed;
        $tenant->destroyed_at = now();
        $tenant->recordStep(99, 'destroyed');
    }
}
