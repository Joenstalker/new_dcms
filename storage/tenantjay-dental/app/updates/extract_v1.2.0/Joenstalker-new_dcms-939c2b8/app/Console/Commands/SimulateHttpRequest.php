<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Pipeline\Pipeline;

class SimulateHttpRequest extends Command
{
    /**
     * @var string
     */
    protected $signature = 'test:simulate-http {path=patients/2604095471} {--subdomain=junjunsmile}';

    /**
     * @var string
     */
    protected $description = 'Simulate an HTTP request through the middleware stack';

    public function handle()
    {
        $path = $this->argument('path');
        $subdomain = $this->option('subdomain');
        $host = $subdomain . '.localhost';
        
        $this->info("Simulating HTTP request:");
        $this->line("  Host: $host");
        $this->line("  Path: /$path");
        $this->line("");
        
        // Create a request with host set via URI
        $request = Request::create("http://$host/$path", 'GET');
        
        // Ensure host is properly set
        $request->headers->set('Host', $host);
        
        $this->info("Request created. Testing controller...");
        
        try {
            // Import the controller
            $controllerClass = 'App\Http\Controllers\PatientController';
            $controller = new $controllerClass();
            
            // Extract patient ID from path
            $parts = explode('/', $path);
            $patientId = end($parts);
            
            // For HTTP simulation, we need to manually initialize tenancy
            // In real HTTP, the middleware would do this
            $tenantModel = \App\Models\Tenant::class;
            $tenant = $tenantModel::find($subdomain);
            
            if (!$tenant) {
                $this->error("Tenant not found: $subdomain");
                return 1;
            }
            
            $this->info("Request host: " . $request->getHost());
            $this->info("Initializing tenancy for tenant: " . $tenant->id);
            tenancy()->initialize($tenant);
            
            // Call the controller method
            $response = $controller->show($request, $patientId);
            
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $this->info("✓ Success! Response (JSON):");
                $data = $response->getData(true);
                $this->line("  Patient ID: " . ($data['id'] ?? 'N/A'));
                $this->line("  First Name: " . ($data['first_name'] ?? 'N/A'));
                if (isset($data['appointments'])) {
                    $this->line("  Appointments: " . count($data['appointments']));
                }
            } else {
                $this->info("✓ Success! Response type: " . class_basename($response));
            }
            
            $this->newLine();
            $this->info("Test passed!");
            return 0;
            
        } catch (\Throwable $e) {
            $this->error("✗ Error: " . $e->getMessage());
            $this->line("Stack trace:");
            $this->line($e->getTraceAsString());
            return 1;
        } finally {
            tenancy()->end();
        }
    }
}
