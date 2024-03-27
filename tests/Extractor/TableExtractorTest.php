<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Test\Extractor;

use CyberSpectrum\I18N\Contao\Extractor\TableExtractor;
use PHPUnit\Framework\TestCase;

/** @covers \CyberSpectrum\I18N\Contao\Extractor\TableExtractor */
class TableExtractorTest extends TestCase
{
    public function testReadsCorrectly(): void
    {
        $array = [
            'items' => serialize([
                0 => [
                    0 => '0.0',
                    1 => '0.1',
                ],
                1 => [
                    0 => '1.0',
                    1 => '1.1',
                ],
            ])
        ];


        $extractor = new TableExtractor('items');

        $this->assertSame('items', $extractor->name());
        $this->assertTrue($extractor->supports($array));
        $this->assertSame(
            [
                'row0.col0',
                'row0.col1',
                'row1.col0',
                'row1.col1',
            ],
            \iterator_to_array($extractor->keys($array))
        );

        $this->assertSame('0.0', $extractor->get('row0.col0', $array));
        $this->assertSame('0.1', $extractor->get('row0.col1', $array));
        $this->assertSame('1.0', $extractor->get('row1.col0', $array));
        $this->assertSame('1.1', $extractor->get('row1.col1', $array));
    }

    public function testWritesCorrectly(): void
    {
        $array = [
            'items' => null,
        ];

        $extractor = new TableExtractor('items');

        $extractor->set('row0.col0', $array, '0.0');
        $extractor->set('row0.col1', $array, '0.1');
        $extractor->set('row1.col0', $array, '1.0');
        $extractor->set('row1.col1', $array, '1.1');

        $this->assertSame([
            'items' => serialize([
                0 => [
                    0 => '0.0',
                    1 => '0.1',
                ],
                1 => [
                    0 => '1.0',
                    1 => '1.1',
                ],
            ])
        ], $array);
    }
}
