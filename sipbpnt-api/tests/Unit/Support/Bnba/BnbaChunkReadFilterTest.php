<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Bnba;

use App\Support\Bnba\BnbaChunkReadFilter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BnbaChunkReadFilterTest extends TestCase
{
    public function test_filter_only_accepts_requested_rows(): void
    {
        $filter = new BnbaChunkReadFilter(
            startRow: 10,
            endRow: 20,
        );

        $this->assertFalse(
            $filter->readCell('A', 9)
        );

        $this->assertTrue(
            $filter->readCell('A', 10)
        );

        $this->assertTrue(
            $filter->readCell('Z', 20)
        );

        $this->assertFalse(
            $filter->readCell('A', 21)
        );
    }

    public function test_filter_can_be_restricted_to_one_worksheet(): void
    {
        $filter = new BnbaChunkReadFilter(
            startRow: 2,
            endRow: 5,
            worksheetName: 'BNBA',
        );

        $this->assertTrue(
            $filter->readCell(
                'A',
                2,
                'BNBA'
            )
        );

        $this->assertFalse(
            $filter->readCell(
                'A',
                2,
                'LAINNYA'
            )
        );
    }

    public function test_filter_rejects_invalid_range(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        new BnbaChunkReadFilter(
            startRow: 10,
            endRow: 9,
        );
    }
}