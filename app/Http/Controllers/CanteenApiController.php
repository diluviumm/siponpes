<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use App\Models\School;
use App\Services\CanteenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CanteenApiController extends Controller
{
    public function transaction(Request $request): JsonResponse
    {
        $school = $this->resolveSchool($request);

        if (! $school) {
            Log::warning('Unauthorized canteen API access attempt.', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'santri_card_token' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'canteen_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $santri = Santri::where('santri_card_token', $request->input('santri_card_token'))
            ->where('branch_id', $school->id)
            ->withoutTrashed()
            ->first();

        if (! $santri) {
            Log::warning('Canteen transaction failed: invalid card token.', [
                'card_token' => $request->input('santri_card_token'),
                'canteen_name' => $request->input('canteen_name'),
            ]);

            return response()->json([
                'message' => 'Santri not found',
            ], 404);
        }

        $amount = (float) $request->input('amount');

        $result = app(CanteenService::class)->purchase(
            $santri,
            $amount,
            $request->input('canteen_name'),
            $school->id
        );

        if ($result['status'] === 'wallet_missing') {
            Log::warning('Canteen transaction failed: missing wallet.', [
                'santri_id' => $santri->id,
                'canteen_name' => $request->input('canteen_name'),
            ]);

            return response()->json([
                'message' => 'Wallet not found for santri',
            ], 404);
        }

        if ($result['status'] === 'insufficient_balance') {
            Log::warning('Canteen transaction failed: insufficient balance.', [
                'santri_id' => $santri->id,
                'canteen_name' => $request->input('canteen_name'),
            ]);

            return response()->json([
                'message' => 'Insufficient wallet balance',
            ], 400);
        }

        if ($result['status'] === 'daily_limit_exceeded') {
            Log::warning('Canteen transaction failed: daily spending limit exceeded.', [
                'santri_id' => $santri->id,
                'canteen_name' => $request->input('canteen_name'),
            ]);

            return response()->json([
                'message' => 'Daily spending limit exceeded',
            ], 400);
        }

        Log::info('Canteen transaction completed.', [
            'santri_id' => $santri->id,
            'amount' => $amount,
            'canteen_name' => $request->input('canteen_name'),
            'school_id' => $school->id,
        ]);

        return response()->json([
            'message' => 'Transaction completed successfully',
            'data' => [
                'santri_id' => $santri->id,
                'wallet_balance' => $result['balance'],
                'amount' => $amount,
                'canteen_name' => $request->input('canteen_name'),
            ],
        ], 200);
    }

    protected function resolveSchool(Request $request): ?School
    {
        $apiKey = $request->header('X-API-KEY') ?: $request->bearerToken();

        if (! $apiKey) {
            return null;
        }

        $school = School::where('api_key', $apiKey)->first();

        if (! $school || ! $school->isActive()) {
            return null;
        }

        return $school;
    }
}
