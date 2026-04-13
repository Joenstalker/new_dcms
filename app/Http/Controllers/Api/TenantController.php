<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * API Tenant Controller
 * 
 * Provides API endpoints for tenant management including:
 * - List all tenants with database information
 * - Switch between tenant databases (for testing)
 */
class TenantController extends Controller
{
    /**
     * List all tenants with database information
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $tenants = Tenant::with(['domains'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($tenant) {
            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'status' => $tenant->status,
                'database_name' => $tenant->getDatabaseName(),
                'database_connection' => $tenant->getDatabaseConnectionName(),
                'uses_hashed_naming' => $tenant->usesHashedDatabaseName(),
                'domains' => $tenant->domains->map(function ($domain) {
                    return $domain->domain;
                }),
                'created_at' => $tenant->created_at->toIso8601String(),
                'updated_at' => $tenant->updated_at->toIso8601String(),
            ];
            });

        return response()->json([
            'success' => true,
            'data' => $tenants,
            'meta' => [
                'total' => $tenants->count(),
            ],
        ]);
    }

    /**
     * Get a specific tenant with database information
     * 
     * @param Tenant $tenant
     * @return JsonResponse
     */
    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load(['domains', 'subscriptions.plan']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'status' => $tenant->status,
                'database_name' => $tenant->getDatabaseName(),
                'database_connection' => $tenant->getDatabaseConnectionName(),
                'uses_hashed_naming' => $tenant->usesHashedDatabaseName(),
                'domains' => $tenant->domains->map(function ($domain) {
            return $domain->domain;
        }),
                'created_at' => $tenant->created_at->toIso8601String(),
                'updated_at' => $tenant->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Switch to a tenant's database (for testing)
     * 
     * This endpoint allows testing the connection to a tenant's database.
     * 
     * @param Request $request
     * @param Tenant $tenant
     * @return JsonResponse
     */
    public function switchDatabase(Request $request, Tenant $tenant): JsonResponse
    {
        try {
            // Get the connection manager
            $connectionManager = app(\App\Providers\TenantConnectionManager::class);

            // Try to connect
            $connectionName = $connectionManager->connect($tenant);

            // Test the connection
            DB::connection($connectionName)->getPdo();

            return response()->json([
                'success' => true,
                'message' => "Successfully connected to tenant database",
                'data' => [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'database_name' => $tenant->getDatabaseName(),
                    'connection_name' => $connectionName,
                ],
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to tenant database',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a tenant
     * 
     * @param Request $request
     * @param Tenant $tenant
     * @return JsonResponse
     */
    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|min:1|max:255',
            'status' => 'sometimes|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $tenant->update($request->only(['name', 'status']));

        return response()->json([
            'success' => true,
            'message' => 'Tenant updated successfully',
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'status' => $tenant->status,
                'database_name' => $tenant->getDatabaseName(),
                'updated_at' => $tenant->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Delete a tenant
     * 
     * @param Tenant $tenant
     * @return JsonResponse
     */
    public function destroy(Tenant $tenant): JsonResponse
    {
        $tenantId = $tenant->id;
        $databaseName = $tenant->getDatabaseName();

        // Delete the tenant (this will also trigger database deletion via TenancyServiceProvider)
        $tenant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tenant deleted successfully',
            'data' => [
                'tenant_id' => $tenantId,
                'database_name' => $databaseName,
            ],
        ]);
    }

}