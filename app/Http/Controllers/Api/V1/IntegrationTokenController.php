<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\IntegrationTokenRequest;
use App\Http\Resources\IntegrationTokenResource;
use App\Services\IntegrationTokenResolverService;

class IntegrationTokenController extends Controller
{
    public function __construct(
        private IntegrationTokenResolverService $resolver
    ) {}

    public function show(IntegrationTokenRequest $request)
    {
        $account = $this->resolver->resolve(
            companyId: (int) $request->company_id,
            userId: (int) $request->user_id,
            provider: $request->provider,
            serviceType: $request->service_type
        );

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Integration account not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new IntegrationTokenResource($account),
        ]);
    }
}