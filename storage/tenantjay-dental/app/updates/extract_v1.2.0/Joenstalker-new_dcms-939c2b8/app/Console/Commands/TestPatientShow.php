<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\PatientController;
use App\Models\Tenant;

class TestPatientShow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:patient-show {tenant_id} {patient_id}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Test the patient show endpoint';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant_id');
        $patientId = $this->argument('patient_id');
        
        $this->info("Testing patient show for tenant: $tenantId, patient: $patientId");
        
        // Find and initialize tenant
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant not found: $tenantId");
            return 1;
        }
        
        // Initialize tenancy
        tenancy()->initialize($tenant);
        
        // Create a mock request
        $request = new Request();
        $request->setMethod('GET');
        $request->setRequestFormat('json');
        
        // Create controller and call show method
        $controller = new PatientController();
        
        try {
            $response = $controller->show($request, $patientId);
            
            // Check if response is JSON
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $this->info('Success! Patient data:');
                $this->line(json_encode($response->getData(), JSON_PRETTY_PRINT));
            } else {
                $this->info('Success! Response:');
                $this->line(get_class($response));
            }
            
            return 0;
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        } finally {
            tenancy()->end();
        }
    }
}
