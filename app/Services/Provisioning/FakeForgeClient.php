<?php

namespace App\Services\Provisioning;

use Illuminate\Support\Facades\Log;
use Laravel\Forge\Resources\Daemon;
use Laravel\Forge\Resources\Database;
use Laravel\Forge\Resources\Recipe;
use Laravel\Forge\Resources\Server;
use Laravel\Forge\Resources\Site;
use Laravel\Forge\Resources\SSLCertificate;

/**
 * Non-network Forge client used when PROVISIONING_DRY_RUN=true. Every call
 * logs what would have happened and returns a plausible resource stub so the
 * real provisioner code can advance through all 22 steps without hitting
 * Forge. Resource IDs are deterministic per (method, serverId, name).
 */
class FakeForgeClient extends ForgeClient
{
    public function __construct()
    {
        // Bypass parent: no token required in dry-run.
    }

    public function sdk(): \Laravel\Forge\Forge
    {
        throw new \RuntimeException('sdk() not available in FakeForgeClient. Use the public wrapper methods or guard dry-run code paths.');
    }

    public function createServer(string $name, array $options = []): Server
    {
        $id = $this->stableId('server', $name);
        $this->log('createServer', compact('name', 'options', 'id'));

        return $this->stubServer($id, $name, false);
    }

    public function getServer(int $serverId): Server
    {
        $this->log('getServer', compact('serverId'));

        return $this->stubServer($serverId, "fake-server-{$serverId}", true);
    }

    public function serverIsReady(int $serverId): bool
    {
        $this->log('serverIsReady', ['serverId' => $serverId, 'return' => true]);

        return true;
    }

    public function createSite(int $serverId, string $domain, array $options = []): Site
    {
        $id = $this->stableId('site', "{$serverId}:{$domain}");
        $this->log('createSite', compact('serverId', 'domain', 'options', 'id'));

        return $this->stubSite($id, $domain);
    }

    public function listSites(int $serverId): array
    {
        $this->log('listSites', compact('serverId'));

        return [];
    }

    public function findSiteByDomain(int $serverId, string $domain): ?Site
    {
        $this->log('findSiteByDomain', compact('serverId', 'domain'));

        return null;
    }

    public function getSite(int $serverId, int $siteId): Site
    {
        $this->log('getSite', compact('serverId', 'siteId'));

        return $this->stubSite($siteId, "fake-site-{$siteId}");
    }

    public function installGitRepository(int $serverId, int $siteId, string $repo, string $branch = 'main', string $provider = 'github'): void
    {
        $this->log('installGitRepository', compact('serverId', 'siteId', 'repo', 'branch', 'provider'));
    }

    public function obtainLetsEncryptCertificate(int $serverId, int $siteId, array $domains): SSLCertificate
    {
        $id = $this->stableId('cert', "{$serverId}:{$siteId}");
        $this->log('obtainLetsEncryptCertificate', compact('serverId', 'siteId', 'domains', 'id'));

        $cert = new SSLCertificate([], null);
        $cert->id = $id;
        $cert->domain = $domains[0] ?? null;
        $cert->active = true;

        return $cert;
    }

    public function listCertificates(int $serverId, int $siteId): array
    {
        $this->log('listCertificates', compact('serverId', 'siteId'));

        return [];
    }

    public function activeCertificateExists(int $serverId, int $siteId): bool
    {
        $this->log('activeCertificateExists', ['serverId' => $serverId, 'siteId' => $siteId, 'return' => false]);

        return false;
    }

    public function updateSiteEnvironment(int $serverId, int $siteId, string $envContents): void
    {
        $this->log('updateSiteEnvironment', [
            'serverId' => $serverId,
            'siteId' => $siteId,
            'env_bytes' => strlen($envContents),
        ]);
    }

    public function siteEnvironment(int $serverId, int $siteId): string
    {
        $this->log('siteEnvironment', compact('serverId', 'siteId'));

        return "# dry-run stub env\n";
    }

    public function updateDeployScript(int $serverId, int $siteId, string $script): void
    {
        $this->log('updateDeployScript', [
            'serverId' => $serverId,
            'siteId' => $siteId,
            'script_bytes' => strlen($script),
        ]);
    }

    public function deploySite(int $serverId, int $siteId): void
    {
        $this->log('deploySite', compact('serverId', 'siteId'));
    }

    public function createDatabase(int $serverId, string $name, ?string $user = null, ?string $password = null): Database
    {
        $id = $this->stableId('db', "{$serverId}:{$name}");
        $this->log('createDatabase', [
            'serverId' => $serverId,
            'name' => $name,
            'user' => $user,
            'password_length' => $password ? strlen($password) : 0,
            'id' => $id,
        ]);

        $db = new Database([], null);
        $db->id = $id;
        $db->name = $name;

        return $db;
    }

    public function listDatabases(int $serverId): array
    {
        $this->log('listDatabases', compact('serverId'));

        return [];
    }

    public function findDatabaseByName(int $serverId, string $name): ?Database
    {
        $this->log('findDatabaseByName', compact('serverId', 'name'));

        return null;
    }

    public function createDaemon(int $serverId, string $command, string $user = 'forge', ?string $directory = null): Daemon
    {
        $id = $this->stableId('daemon', "{$serverId}:{$command}");
        $this->log('createDaemon', compact('serverId', 'command', 'user', 'directory', 'id'));

        $d = new Daemon([], null);
        $d->id = $id;
        $d->command = $command;

        return $d;
    }

    public function listDaemons(int $serverId): array
    {
        $this->log('listDaemons', compact('serverId'));

        return [];
    }

    public function findDaemonByCommand(int $serverId, string $commandSubstring): ?Daemon
    {
        $this->log('findDaemonByCommand', compact('serverId', 'commandSubstring'));

        return null;
    }

    public function createRecipe(string $name, string $script, string $user = 'root'): Recipe
    {
        $id = $this->stableId('recipe', $name);
        $this->log('createRecipe', [
            'name' => $name,
            'user' => $user,
            'script_bytes' => strlen($script),
            'id' => $id,
        ]);

        $r = new Recipe([], null);
        $r->id = $id;
        $r->name = $name;

        return $r;
    }

    public function listRecipes(): array
    {
        $this->log('listRecipes', []);

        return [];
    }

    public function findRecipeByName(string $name): ?Recipe
    {
        $this->log('findRecipeByName', compact('name'));

        return null;
    }

    public function runRecipe(int $recipeId, array $serverIds): void
    {
        $this->log('runRecipe', compact('recipeId', 'serverIds'));
    }

    public function enableScheduler(int $serverId, string $command): void
    {
        $this->log('enableScheduler', compact('serverId', 'command'));
    }

    public function destroyServer(int $serverId): void
    {
        $this->log('destroyServer', compact('serverId'));
    }

    private function stubServer(int $id, string $name, bool $isReady): Server
    {
        $s = new Server([], null);
        $s->id = $id;
        $s->name = $name;
        $s->isReady = $isReady;
        $s->ipAddress = '203.0.113.'.(($id % 250) + 1);

        return $s;
    }

    private function stubSite(int $id, string $domain): Site
    {
        $s = new Site([], null);
        $s->id = $id;
        $s->name = $domain;
        $s->repository = null;

        return $s;
    }

    private function stableId(string $kind, string $name): int
    {
        return abs(crc32("{$kind}:{$name}")) % 90000 + 10000;
    }

    private function log(string $method, array $context): void
    {
        Log::info('[dry-run Forge] '.$method, $context);
    }
}
