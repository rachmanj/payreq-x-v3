<?php

namespace App\Console\Commands;

use App\Services\SapService;
use Illuminate\Console\Command;

class ListSapServiceItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:list-service-items';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List available service items from SAP B1 to find valid ItemCode for AR Invoice';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Querying SAP B1 for service items...');
        
        try {
            $sapService = app(SapService::class);
            $serviceItems = $sapService->getServiceItems();
            
            if (empty($serviceItems)) {
                $this->warn('No service items found in SAP B1.');
                $this->info('');
                $this->info('This could mean:');
                $this->line('1. No service items exist in your SAP B1 system');
                $this->line('2. The query failed (check logs for details)');
                $this->line('3. You need to create a service item in SAP B1 first');
                $this->info('');
                $this->info('To create a service item in SAP B1:');
                $this->line('1. Go to Inventory > Items > Items');
                $this->line('2. Create a new item with ItemType = "Service"');
                $this->line('3. Use a simple code like "SERVICE" or "AR-SERVICE"');
                $this->line('4. Set the GL Account to your AR Account (11401039)');
                $this->info('');
                $this->info('Then set in your .env file:');
                $this->line('SAP_AR_INVOICE_DEFAULT_ITEM_CODE=YOUR_ITEM_CODE');
                return 1;
            }
            
            $this->info('Found ' . count($serviceItems) . ' service item(s):');
            $this->info('');
            
            $headers = ['ItemCode', 'ItemName', 'ItemType'];
            $rows = [];
            
            foreach ($serviceItems as $item) {
                $rows[] = [
                    $item['ItemCode'] ?? 'N/A',
                    $item['ItemName'] ?? 'N/A',
                    $item['ItemType'] ?? 'N/A',
                ];
            }
            
            $this->table($headers, $rows);
            
            $this->info('');
            $this->info('Recommended ItemCode: ' . ($serviceItems[0]['ItemCode'] ?? 'N/A'));
            $this->info('');
            $this->info('Add this to your .env file:');
            $this->line('SAP_AR_INVOICE_DEFAULT_ITEM_CODE=' . ($serviceItems[0]['ItemCode'] ?? 'YOUR_ITEM_CODE'));
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to query SAP B1: ' . $e->getMessage());
            $this->info('');
            $this->info('Please check:');
            $this->line('1. SAP B1 server is accessible');
            $this->line('2. SAP credentials are correct in .env file');
            $this->line('3. Check logs for more details');
            return 1;
        }
    }
}
