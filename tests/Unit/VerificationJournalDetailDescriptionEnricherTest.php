<?php

namespace Tests\Unit;

use App\Models\RealizationDetail;
use App\Support\VerificationJournalDetailDescriptionEnricher;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerificationJournalDetailDescriptionEnricherTest extends TestCase
{
    #[Test]
    public function it_builds_additional_info_from_realization_detail_fields(): void
    {
        $detail = new RealizationDetail([
            'unit_no' => 'VA 061',
            'nopol' => 'B 1455DFF',
            'type' => 'fuel',
            'qty' => 50,
            'uom' => 'L',
            'km_position' => 62671,
        ]);

        $this->assertSame(
            [
                'Unit: VA 061',
                'Nopol: B 1455DFF',
                'Type: fuel',
                'Qty: 50 L',
                'HM: 62671',
            ],
            VerificationJournalDetailDescriptionEnricher::buildAdditionalInfo($detail)
        );
    }

    #[Test]
    public function it_omits_empty_realization_detail_fields(): void
    {
        $detail = new RealizationDetail([
            'description' => 'Fuel operasional PRC Manager periode 27 s/d 31 Juli 2026',
        ]);

        $this->assertSame([], VerificationJournalDetailDescriptionEnricher::buildAdditionalInfo($detail));
    }
}
