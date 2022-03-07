<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Test\Extractor;

use CyberSpectrum\I18N\Contao\Extractor\TextExtractor;
use PHPUnit\Framework\TestCase;

/** @covers \CyberSpectrum\I18N\Contao\Extractor\TextExtractor */
class TextExtractorTest extends TestCase
{
    public function testReadsCorrectly(): void
    {
        $array = ['item' => 'text'];

        $extractor = new TextExtractor('item');

        $this->assertSame('item', $extractor->name());
        $this->assertTrue($extractor->supports($array));
        $this->assertSame('text', $extractor->get($array));
    }

    public function testWritesCorrectly(): void
    {
        $array = ['item' => null];

        $extractor = new TextExtractor('item');

        $extractor->set($array, 'text');

        $this->assertSame(['item' => 'text'], $array);
    }
}
