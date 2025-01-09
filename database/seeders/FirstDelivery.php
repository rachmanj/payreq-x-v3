<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FirstDelivery extends Seeder
{
    public function run(): void
    {
        DB::table('deliveries')->insert([
            [
                'created_by' => 1, // Assuming user with ID 1
                'delivery_number' => 'DEL-001',
                'document_date' => '2024-12-31', // Changed from delivery_date to document_date
                'origin' => '000H',
                'destination' => '000H',
                'sent_date' => '2024-12-31',
                'received_date' => '2024-12-31',
                'recipient_name' => 'Administrator',
                'received_by' => 1,
                'status' => 'delivered', // Changed from delivery_status to status
            ],
        ]);
    }
}
