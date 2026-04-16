<?php

namespace App\Services\Provisioning;

use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Daemon;
use Laravel\Forge\Resources\Database;
use Laravel\Forge\Resources\Recipe;
use Laravel\Forge\Resources\Server;
use Laravel\Forge\Resources\Site;
use Laravel\Forge\Resources\Certificate;
use RuntimeException;

/**
 * Thin wrapper around laravel/forge-sdk for the specific operations the
 * tenant provisioning pipeline needs. Every method is idempotent where
 * possible — looking up existing artifacts by ID or name before creating.
 */
class ForgeClient
{
    private Forge $forge;

    public function __construct(?string $token = null)
    {
        $token ??= config('services.forge.token');

        if (! $token) {
            throw new RuntimeException('FORGE_API_TOKEN is not configured.');
        }

        $this->forge = new Forge($token);
    }

    public function sdk(): Forge
    {
        return $this->forge;
    }

    /**
     * Create a new DigitalOcean server via Forge.
     *
     * @param  array<string, mixed>  $options  Overrides (region, size, etc.)
     */
    public function createServer(string $name, array $options = []): Server
    {
        $defaults = [
            'provider' => 'ocean2',                        // DigitalOcean v2
            'credential_id' => (int) config('services.forge.do_credential_id'),
            'region' => config('services.forge.default_region', 'nyc3'),
            'size' => 's-2vcpu-2gb',
            'database_type' => 'postgres16',
            'php_version' => 'php83',
            'ubuntu_version' => '24.04',
            'database' => 'forge',                         // default DB name
        ];

        $payload = array_merge($defaults, $options, ['name' => $name]);

        return $this->forge->createServer($payload);
    }

    public function getServer(int $serverId): Server
    {
        return $this->forge->server($serverId);
    }

    /**
     * True once provisioning on the server is finished and it's ready for
     * sites/daemons. Poll this after createServer().
     */
    public function serverIsReady(int $serverId): bool
    {
        return (bool) $this->forge->server($serverId)->isReady;
    }

    public function createSite(int $serverId, string $domain, array $options = []): Site
    {
        $defaults = [
            'project_type' => 'php',
            'aliases' => [],
            'directory' => '/public',
            'isolated' => false,
            'php_version' => 'php83',
        ];

        return $this->forge->createSite(
            $serverId,
            array_merge($defaults, $options, ['domain' => $domain]),
        );
    }

    /**
     * @return array<int, Site>
     */
    public function listSites(int $serverId): array
    {
        return $this->forge->sites($serverId);
    }

    public function findSiteByDomain(int $serverId, string $domain): ?Site
    {
        foreach ($this->listSites($serverId) as $site) {
            if ($site->name === $domain) {
                return $site;
            }
        }

        return null;
    }

    public function getSite(int $serverId, int $siteId): Site
    {
        return $this->forge->site($serverId, $siteId);
    }

    public function installGitRepository(int $serverId, int $siteId, string $repo, string $branch = 'main', string $provider = 'github'): void
    {
        $this->forge->installGitRepositoryOnSite($serverId, $siteId, [
            'provider' => $provider,
            'repository' => $repo,
            'branch' => $branch,
            'composer' => true,
        ]);
    }

    public function obtainLetsEncryptCertificate(int $serverId, int $siteId, array $domains): Certificate
    {
        return $this->forge->obtainLetsEncryptCertificate(
            $serverId,
            $siteId,
            ['domains' => $domains],
            false, // wait=false — we poll separately
        );
    }

    /**
     * @return array<int, Certificate>
     */
    public function listCertificates(int $serverId, int $siteId): array
    {
        return $this->forge->certificates($serverId, $siteId);
    }

    public function activeCertificateExists(int $serverId, int $siteId): bool
    {
        foreach ($this->listCertificates($serverId, $siteId) as $cert) {
            if ($cert->active) {
                return true;
            }
        }

        return false;
    }

    public function updateSiteEnvironment(int $serverId, int $siteId, string $envContents): void
    {
        $this->forge->updateSiteEnvironmentFile($serverId, $siteId, $envContents);
    }

    public function siteEnvironment(int $serverId, int $siteId): string
    {
        return $this->forge->siteEnvironmentFile($serverId, $siteId);
    }

    public function updateDeployScript(int $serverId, int $siteId, string $script): void
    {
        $this->forge->updateSiteDeploymentScript($serverId, $siteId, $script);
    }

    public function deploySite(int $serverId, int $siteId): void
    {
        $this->forge->deploySite($serverId, $siteId);
    }

    public function createDatabase(int $serverId, string $name, ?string $user = null, ?string $password = null): Database
    {
        return $this->forge->createDatabase($serverId, array_filter([
            'name' => $name,
            'user' => $user,
            'password' => $password,
        ]));
    }

    /**
     * @return array<int, Database>
     */
    public function listDatabases(int $serverId): array
    {
        return $this->forge->databases($serverId);
    }

    public function findDatabaseByName(int $serverId, string $name): ?Database
    {
        foreach ($this->listDatabases($serverId) as $db) {
            if ($db->name === $name) {
                return $db;
            }
        }

        return null;
    }

    public function createDaemon(int $serverId, string $command, string $user = 'forge', ?string $directory = null): Daemon
    {
        return $this->forge->createDaemon($serverId, array_filter([
            'command' => $command,
            'user' => $user,
            'directory' => $directory,
        ]));
    }

    /**
     * @return array<int, Daemon>
     */
    public function listDaemons(int $serverId): array
    {
        return $this->forge->daemons($serverId);
    }

    public function findDaemonByCommand(int $serverId, string $commandSubstring): ?Daemon
    {
        foreach ($this->listDaemons($serverId) as $daemon) {
            if (str_contains($daemon->command, $commandSubstring)) {
                return $daemon;
            }
        }

        return null;
    }

    public function createRecipe(string $name, string $script, string $user = 'root'): Recipe
    {
        return $this->forge->createRecipe([
            'name' => $name,
            'user' => $user,
            'script' => $script,
        ]);
    }

    /**
     * @return array<int, Recipe>
     */
    public function listRecipes(): array
    {
        return $this->forge->recipes();
    }

    public function findRecipeByName(string $name): ?Recipe
    {
        foreach ($this->listRecipes() as $recipe) {
            if ($recipe->name === $name) {
                return $recipe;
            }
        }

        return null;
    }

    public function runRecipe(int $recipeId, array $serverIds): void
    {
        $this->forge->runRecipe($recipeId, ['servers' => $serverIds]);
    }

    public function enableScheduler(int $serverId, string $command): void
    {
        $this->forge->createJob($serverId, [
            'command' => $command,
            'frequency' => 'minutely',
            'user' => 'forge',
        ]);
    }

    public function executeSiteCommand(int $serverId, int $siteId, string $command): int
    {
        $result = $this->forge->executeSiteCommand($serverId, $siteId, ['command' => $command]);

        return $result->id;
    }

    public function getSiteCommandOutput(int $serverId, int $siteId, int $commandId): ?string
    {
        $commands = $this->forge->getSiteCommand($serverId, $siteId, $commandId);

        if (empty($commands)) {
            return null;
        }

        return $commands[0]?->output;
    }

    public function destroyServer(int $serverId): void
    {
        $this->forge->deleteServer($serverId);
    }
}
