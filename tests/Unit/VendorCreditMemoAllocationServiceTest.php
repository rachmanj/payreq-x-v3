<?php

namespace Tests\Unit;

use App\Services\VendorCreditMemoAllocationService;
use PHPUnit\Framework\TestCase;

class VendorCreditMemoAllocationServiceTest extends TestCase
{
    public function test_allocate_oldest_first_fills_invoices_until_credit_is_exhausted(): void
    {
        $service = new VendorCreditMemoAllocationService;

        $allocations = $service->allocateOldestFirst([
            [
                'doc_entry' => 20,
                'doc_num' => 200,
                'doc_date' => '2026-03-01',
                'remaining_balance' => 400000,
            ],
            [
                'doc_entry' => 10,
                'doc_num' => 100,
                'doc_date' => '2026-01-15',
                'remaining_balance' => 300000,
            ],
            [
                'doc_entry' => 30,
                'doc_num' => 300,
                'doc_date' => '2026-04-01',
                'remaining_balance' => 500000,
            ],
        ], 600000);

        $this->assertSame([
            ['doc_entry' => 10, 'doc_num' => 100, 'num_at_card' => null, 'amount' => 300000.0],
            ['doc_entry' => 20, 'doc_num' => 200, 'num_at_card' => null, 'amount' => 300000.0],
        ], $allocations);
    }
}
