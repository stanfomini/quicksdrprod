<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Stanfomini\QuickClaimstaker\Models\Agent;
use Stanfomini\QuickClaimstaker\Models\Company;

#[Signature('claimstaker:create-admin {email : Admin email} {name : Admin name} {--business= : Business name (used to create the initial Company; defaults to the admin name)} {--timezone=America/Denver : Timezone for the initial Company} {--with-reset-url : Also generate a password reset URL and print RESET_URL=https://… to stdout}')]
#[Description("Idempotently ensure a Company, admin User, and Agent(role=admin) exist for the given email. Fresh installs get a random placeholder password; --with-reset-url is the expected flag during SaaS provisioning — the orchestrator captures the reset URL from stdout and emails it to the admin.")]
class CreateAdminCommand extends Command
{
    public function handle(): int
    {
        $email = trim((string) $this->argument('email'));
        $name = trim((string) $this->argument('name'));

        try {
            validator(
                ['email' => $email, 'name' => $name],
                [
                    'email' => ['required', 'email:rfc', 'max:255'],
                    'name' => ['required', 'string', 'max:255'],
                ],
            )->validate();
        } catch (ValidationException $e) {
            $this->error('Invalid input: '.json_encode($e->errors()));

            return self::FAILURE;
        }

        $hasTypeColumn = Schema::hasColumn('users', 'type');
        $businessName = trim((string) ($this->option('business') ?: $name));
        $timezone = (string) $this->option('timezone');

        $user = DB::transaction(function () use ($email, $name, $businessName, $timezone, $hasTypeColumn) {
            // Agent requires a company_id — ensure one exists first. We
            // take the first active company if any (handles re-runs); else
            // create one from the business name.
            $company = Company::query()->where('is_active', true)->orderBy('id')->first();
            if (! $company) {
                $company = Company::create([
                    'name' => $businessName ?: 'Default',
                    'timezone' => $timezone,
                    'is_active' => true,
                ]);
                $this->info("Created Company id={$company->id} name={$company->name}");
            } else {
                $this->info("Using existing Company id={$company->id} name={$company->name}");
            }

            $user = User::firstOrNew(['email' => $email]);

            if (! $user->exists) {
                $user->name = $name;
                $user->password = bcrypt(Str::random(32));
                $user->email_verified_at = now();
                if ($hasTypeColumn) {
                    $user->type = 'internal';
                }
                $user->save();
                $this->info("Created User id={$user->id} email={$user->email}");
            } else {
                if ($hasTypeColumn && ($user->type ?? null) !== 'internal') {
                    $user->type = 'internal';
                    $user->save();
                }
                $this->info("User already exists id={$user->id} email={$user->email}");
            }

            $agent = Agent::firstOrNew([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'role' => 'admin',
            ]);
            if (! $agent->exists) {
                $agent->is_active = true;
                $agent->save();
                $this->info("Created Agent id={$agent->id} role=admin company_id={$company->id}");
            } else {
                $this->info("Agent(admin) already exists id={$agent->id}");
            }

            return $user;
        });

        if ($this->option('with-reset-url')) {
            $token = Password::broker()->createToken($user);
            $url = URL::to(sprintf(
                '/reset-password/%s?email=%s',
                $token,
                urlencode($user->email),
            ));
            $this->line('RESET_URL='.$url);
        }

        return self::SUCCESS;
    }
}
