<?php

namespace App\Http\Controllers\Api;

use App\Helpers\TenantDatabaseHelper;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantDatabaseNamingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * API Tenant Controller
 * 
 * Provides API endpoints for tenant management including:
 * - Preview hashed database name before creation
 * - List all tenants with database information
 * - Switch between tenant databases (for testing)
 */
class TenantController extends Controller
{
    /**
     * The naming service instance.
     */
    protected TenantDatabaseNamingService $namingService;

    /**
     * Constructor
     */
    public function __construct(TenantDatabaseNamingService $namingService)
    {
        $this->namingService = $namingService;
    }

    /**
     * Preview the hashed database name
     * 
     * This endpoint allows previewing what the hashed database name
     * will look like before actually creating a tenant.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function previewDatabaseName(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'domain' => 'required|string|min:1|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $domain = $request->input('domain');

        $preview = $this->namingService->previewHashedDatabaseName($domain);

        // Check if the database name already exists
        $exists = $this->checkDatabaseExists($preview['database_name']);

        return response()->json([
            'success' => true,
            'data' => [
                'database_name' => $preview['database_name'],
                'hash' => $preview['hash'],
                'domain' => $preview['domain'],
                'already_exists' => $exists,
            ],
        ]);
    }

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
                }
                ),
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
     * Create a new tenant with hashed database name
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:1|max:255',
            'domain' => 'nullable|string|min:1|max:255',
            'status' => 'nullable|in:active,inactive,suspended',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $name = $request->input('name');
        $domain = $request->input('domain') ?? $name;

        // Generate the database name from domain
        $databaseName = TenantDatabaseHelper::generateUniqueHashedDatabaseName(
            $domain,
            function ($dbName) {
            return Tenant::where('database_name', $dbName)->exists()
            || $this->checkDatabaseExists($dbName);
        }
        );

        // Generate connection name
        $tenantId = (string)\Illuminate\Support\Str::uuid();
        $connectionName = TenantDatabaseHelper::generateConnectionName($tenantId);

        // Create the tenant
        $tenant = Tenant::create([
            'id' => $tenantId,
            'name' => $name,
            'status' => $request->input('status', 'active'),
            'database_name' => $databaseName,
            'database_connection' => $connectionName,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tenant created successfully',
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'status' => $tenant->status,
                'database_name' => $tenant->getDatabaseName(),
                'database_connection' => $tenant->getDatabaseConnectionName(),
                'created_at' => $tenant->created_at->toIso8601String(),
            ],
        ], 201);
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

    /**
     * Check if a database already exists
     * 
     * @param string $databaseName
     * @return bool
     */
    protected function checkDatabaseExists(string $databaseName): bool
    {
        try {
            $result = \DB::connection('central')
                ->select('SHOW DATABASES LIKE ?', [$databaseName]);

            return count($result) > 0;
        }
        catch (\Exception $e) {
            return false;
        }
    }
}