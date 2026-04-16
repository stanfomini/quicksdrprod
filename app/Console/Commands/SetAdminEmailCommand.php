<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Stanfomini\QuickClaimstaker\Models\Agent;

#[Signature('claimstaker:set-admin-email {email : New email to assign to the tenant admin user} {--user-id= : Disambiguate when multiple admin users exist} {--send-reset : Email a password reset link to the new address after updating}')]
#[Description("Update the tenant's admin User email in place. Finds the admin user by Agent role='admin' (not by old email), changes their email, and optionally emails a password reset link. Safe to run after an orchestrator-side admin_email edit.")]
class SetAdminEmailCommand extends Command
{
    public function handle(): int
    {
        $newEmail = (string) $this->argument('email');

        try {
            validator(
                ['email' => $newEmail],
                ['email' => ['required', 'email:rfc', 'max:255']],
            )->validate();
        } catch (ValidationException $e) {
            $this->error('Invalid email: '.$newEmail);

            return self::FAILURE;
        }

        $query = User::query()
            ->whereIn(
                'id',
                Agent::query()->where('role', 'admin')->pluck('user_id'),
            );

        if ($userId = $this->option('user-id')) {
            $query->where('id', (int) $userId);
        }

        $admins = $query->get();

        if ($admins->isEmpty()) {
            $this->error('No admin user found (no User linked to an Agent with role=admin).');

            return self::FAILURE;
        }

        if ($admins->count() > 1) {
            $this->error('Multiple admin users exist. Re-run with --user-id to pick one:');
            foreach ($admins as $a) {
                $this->line("  id={$a->id}  email={$a->email}  name={$a->name}");
            }

            return self::FAILURE;
        }

        $admin = $admins->first();
        $oldEmail = (string) $admin->email;

        if ($oldEmail === $newEmail) {
            $this->info("NOOP: admin (id={$admin->id}) already has email {$newEmail}.");

            return self::SUCCESS;
        }

        // DB-level unique constraint catches collisions with other users
        // who happen to use the new email. We surface a clear message
        // rather than a raw QueryException.
        if (User::query()->where('email', $newEmail)->where('id', '!=', $admin->id)->exists()) {
            $this->error("Email {$newEmail} is already in use by another user. Aborting.");

            return self::FAILURE;
        }

        DB::transaction(function () use ($admin, $newEmail) {
            $admin->forceFill([
                'email' => $newEmail,
                'email_verified_at' => null,
            ])->save();
        });

        $this->info("OK: admin (id={$admin->id}) email updated {$oldEmail} -> {$newEmail}.");

        if ($this->option('send-reset')) {
            $status = Password::broker()->sendResetLink(['email' => $newEmail]);

            if ($status === Password::RESET_LINK_SENT) {
                $this->info("Password reset link sent to {$newEmail}.");
            } else {
                $this->warn("Reset link dispatch returned status: {$status}");
            }
        }

        return self::SUCCESS;
    }
}
