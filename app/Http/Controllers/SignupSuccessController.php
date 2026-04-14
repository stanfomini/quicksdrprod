<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignupSuccessController extends Controller
{
    public function show(Request $request)
    {
        $tenantId = (int) $request->query('tenant_id');

        $tenant = $tenantId ? Tenant::find($tenantId) : null;

        if (! $tenant) {
            return redirect()->route('signup.pending');
        }

        return view('signup.success', [
            'tenant' => $tenant,
        ]);
    }

    public function status(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'status' => $tenant->status->value,
            'status_label' => $tenant->status->label(),
            'status_color' => $tenant->status->color(),
            'status_step' => $tenant->status_step,
        ]);
    }
}
