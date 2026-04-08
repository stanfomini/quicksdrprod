<?php

use Illuminate\Support\Facades\Broadcast;


use Stanfomini\QuickClaimstaker\Models\Agent as ClaimstakerAgent;

Broadcast::channel('claimstaker.agent.{agentId}', function ($user, int $agentId) {
    return ClaimstakerAgent::where('id', $agentId)->where('user_id', $user->id)->exists();
});

Broadcast::channel('claimstaker.company.{companyId}', function ($user, int $companyId) {
    $agent = ClaimstakerAgent::where('user_id', $user->id)->first();
    return $agent && in_array($companyId, $agent->getCompanyIds(), true);
});