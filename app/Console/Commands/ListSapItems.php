<?php

namespace App\Console\Commands;

use App\Services\SapService;
use Illuminate\Console\Command;

class ListSapItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:list-items {--limit=50 : Maximum number of items to retrieve}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all items from SAP B1 grouped by ItemType to find available item codes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        $this->info("Querying SAP B1 for items (limit: {$limit})...");
        
        try {
            $sapService = app(SapService::class);
            
            // Query items using the public method
            $items = $sapService->getItems($limit);
            
            if (empty($items)) {
                $this->warn('No items found in SAP B1.');
                $this->info('');
                $this->info('This could mean:');
                $this->line('1. No items exist in your SAP B1 system');
                $this->line('2. The query failed (check logs for details)');
                $this->line('3. You need to check SAP B1 permissions');
                return 1;
            }
            
            // Group items by ItemType
            $itemsByType = [];
            foreach ($items as $item) {
                $itemType = $item['ItemType'] ?? 'Unknown';
                if (!isset($itemsByType[$itemType])) {
                    $itemsByType[$itemType] = [];
                }
                $itemsByType[$itemType][] = $item;
            }
            
            $this->info('Found ' . count($items) . ' item(s) grouped by ItemType:');
            $this->info('');
            
            // Show summary by type
            $this->info('Summary by ItemType:');
            foreach ($itemsByType as $type => $typeItems) {
                $this->line("  {$type}: " . count($typeItems) . " item(s)");
            }
            $this->info('');
            
            // Show details for each type
            foreach ($itemsByType as $type => $typeItems) {
                $this->info("ItemType: {$type} (" . count($typeItems) . " items)");
                $this->info(str_repeat('-', 80));
                
                $headers = ['ItemCode', 'ItemName'];
                $rows = [];
                
                foreach ($typeItems as $item) {
                    $rows[] = [
                        $item['ItemCode'] ?? 'N/A',
                        $item['ItemName'] ?? 'N/A',
                    ];
                }
                
                $this->table($headers, $rows);
                $this->info('');
            }
            
            // Recommendations
            $this->info('Recommendations:');
            $this->info('');
            
            if (isset($itemsByType['S'])) {
                $this->line('✓ Service items (ItemType = "S") found. Recommended ItemCode: ' . ($itemsByType['S'][0]['ItemCode'] ?? 'N/A'));
            } else {
                $this->warn('✗ No Service items (ItemType = "S") found.');
            }
            
            if (isset($itemsByType['I'])) {
                $this->line('ℹ Inventory items (ItemType = "I") found. These can be used but Service items are preferred.');
            }
            
            if (isset($itemsByType['L'])) {
                $this->line('ℹ Labor items (ItemType = "L") found. These can be used but Service items are preferred.');
            }
            
            $this->info('');
            $this->info('For AR Invoice, you can use any item type, but Service items are recommended.');
            $this->info('Add the ItemCode to your .env file:');
            
            $recommendedCode = null;
            if (isset($itemsByType['S']) && !empty($itemsByType['S'])) {
                $recommendedCode = $itemsByType['S'][0]['ItemCode'];
            } elseif (isset($itemsByType['L']) && !empty($itemsByType['L'])) {
                $recommendedCode = $itemsByType['L'][0]['ItemCode'];
            } elseif (isset($itemsByType['I']) && !empty($itemsByType['I'])) {
                $recommendedCode = $itemsByType['I'][0]['ItemCode'];
            } elseif (!empty($items)) {
                $recommendedCode = $items[0]['ItemCode'];
            }
            
            if ($recommendedCode) {
                $this->line("SAP_AR_INVOICE_DEFAULT_ITEM_CODE={$recommendedCode}");
            } else {
                $this->line("SAP_AR_INVOICE_DEFAULT_ITEM_CODE=YOUR_ITEM_CODE");
            }
            
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
