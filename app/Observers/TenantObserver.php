<?php

namespace App\Observers;

use App\Enums\LeadStage;
use App\Models\Lead;
use App\Models\Tenant;

class TenantObserver
{
    /**
     * When a tenant is created (someone signed up), backfill any unlinked Leads
     * whose email or phone matches this tenant's admin contact.
     */
    public function created(Tenant $tenant): void
    {
        $this->linkLeads($tenant);
    }

    /**
     * If a tenant's admin email or phone is updated, re-evaluate the linkage.
     */
    public function updated(Tenant $tenant): void
    {
        if ($tenant->wasChanged(['admin_email', 'admin_phone'])) {
            $this->linkLeads($tenant);
        }
    }

    protected function linkLeads(Tenant $tenant): void
    {
        $email = Lead::normalizeEmail($tenant->admin_email);
        $phone = Lead::normalizePhone($tenant->admin_phone);

        if ($email === null && $phone === null) {
            return;
        }

        $leads = Lead::query()
            ->whereNull('tenant_id')
            ->where(function ($q) use ($email, $phone) {
                if ($email !== null) {
                    $q->orWhere('email', $email);
                }
                if ($phone !== null) {
                    $q->orWhere('phone', $phone);
                }
            })
            ->get();

        foreach ($leads as $lead) {
            $lead->tenant_id = $tenant->id;
            // Auto-promote to Won, but never override Lost.
            if ($lead->stage !== LeadStage::Lost) {
                $lead->stage = LeadStage::Won;
            }
            $lead->save();
        }
    }
}
