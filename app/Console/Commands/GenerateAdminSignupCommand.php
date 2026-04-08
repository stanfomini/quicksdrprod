<?php

namespace App\Console\Commands;

use App\Models\AdminSignupToken;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

#[Signature('claimstaker:generate-admin-signup {--email= : Email hint shown on the signup form (not enforced)} {--hours=72 : Hours until the link expires}')]
#[Description('Generate a one-time admin signup URL for tenant orchestration. Prints just the URL to stdout so the caller can capture it.')]
class GenerateAdminSignupCommand extends Command
{
    public function handle(): int
    {
        $token = AdminSignupToken::generate(
            emailHint: $this->option('email') ?: null,
            expiresInHours: (int) $this->option('hours'),
        );

        $this->line(URL::to('/admin-signup/'.$token->token));

        return self::SUCCESS;
    }
}
