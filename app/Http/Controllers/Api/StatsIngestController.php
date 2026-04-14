<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantStat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class StatsIngestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $signature = $request->header('X-Tenant-Signature');

        if (! is_string($signature) || $signature === '') {
            return response()->json(['error' => 'missing signature'], 401);
        }

        $raw = $request->getContent();
        $payload = json_decode($raw, true);

        if (! is_array($payload) || ! isset($payload['tenant_id'])) {
            return response()->json(['error' => 'invalid payload'], 422);
        }

        $tenant = Tenant::find($payload['tenant_id']);

        if ($tenant === null) {
            return response()->json(['error' => 'tenant not found'], 404);
        }

        $hmacKey = $tenant->stats_hmac_key;

        if (! is_string($hmacKey) || $hmacKey === '') {
            return response()->json(['error' => 'tenant not configured'], 401);
        }

        $expected = hash_hmac('sha256', $raw, $hmacKey);

        if (! hash_equals($expected, $signature)) {
            return response()->json(['error' => 'invalid signature'], 401);
        }

        $validator = Validator::make($payload, [
            'tenant_id' => ['required', 'integer'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date'],
            'agent_count' => ['required', 'integer', 'min:0'],
            'call_count' => ['required', 'integer', 'min:0'],
            'total_talk_seconds' => ['required', 'integer', 'min:0'],
            'avg_call_duration_seconds' => ['required', 'integer', 'min:0'],
            'disposition_counts' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $periodStart = Carbon::parse($data['period_start']);
        $periodEnd = Carbon::parse($data['period_end']);

        TenantStat::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'period_start' => $periodStart,
            ],
            [
                'period_end' => $periodEnd,
                'agent_count' => $data['agent_count'],
                'call_count' => $data['call_count'],
                'total_talk_seconds' => $data['total_talk_seconds'],
                'avg_call_duration_seconds' => $data['avg_call_duration_seconds'],
                'disposition_counts' => $data['disposition_counts'] ?? [],
                'ingested_at' => now(),
            ]
        );

        return response()->json(['status' => 'ok']);
    }
}
