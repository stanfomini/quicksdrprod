<?php

namespace App\Livewire\Tenant;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export-only UI shown on tenant servers after the tenant's subscription
 * is cancelled. Active only while `config('app.export_only_until')` is in
 * the future (set on the tenant server's .env by the orchestrator on
 * cancellation).
 *
 * Lets the tenant download CSVs of their call history + contacts before
 * the server is destroyed (7 days after cancel).
 */
#[Layout('components.layouts.app')]
#[Title('Export your data — QuickSDR')]
class Export extends Component
{
    public function exportCalls(): StreamedResponse
    {
        $filename = 'calls-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id', 'from_number', 'to_number', 'direction', 'status',
                'disposition', 'amd_result', 'duration_seconds',
                'talk_seconds', 'started_at', 'ended_at', 'recording_url',
            ]);

            DB::table('claimstaker_calls')->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->id,
                        $row->from_number,
                        $row->to_number,
                        $row->direction,
                        $row->status,
                        $row->disposition,
                        $row->amd_result,
                        $row->duration_seconds,
                        $row->talk_seconds,
                        $row->started_at,
                        $row->ended_at,
                        $row->recording_url,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportContacts(): StreamedResponse
    {
        $filename = 'contacts-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id', 'first_name', 'last_name', 'phone', 'email',
                'company', 'last_disposition', 'total_calls',
                'last_contacted_at', 'created_at',
            ]);

            DB::table('claimstaker_contacts')->orderBy('id')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->id,
                        $row->first_name ?? null,
                        $row->last_name ?? null,
                        $row->phone,
                        $row->email ?? null,
                        $row->company ?? null,
                        $row->last_disposition ?? null,
                        $row->total_calls ?? 0,
                        $row->last_contacted_at ?? null,
                        $row->created_at,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function render()
    {
        $until = config('app.export_only_until');
        $deadline = $until ? Carbon::parse($until) : null;

        $callCount = DB::table('claimstaker_calls')->count();
        $contactCount = DB::table('claimstaker_contacts')->count();

        return view('livewire.tenant.export', [
            'deadline' => $deadline,
            'daysLeft' => $deadline?->diffInDays(now()),
            'callCount' => $callCount,
            'contactCount' => $contactCount,
        ]);
    }
}
