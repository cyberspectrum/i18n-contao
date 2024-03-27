<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Test\Extractor;

use CyberSpectrum\I18N\Contao\Extractor\SerializingCompoundExtractor;
use CyberSpectrum\I18N\Contao\Extractor\StringExtractorInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CyberSpectrum\I18N\Contao\Extractor\AbstractSerializingCompoundExtractor
 * @covers \CyberSpectrum\I18N\Contao\Extractor\SerializingCompoundExtractor
 */
class SerializingCompoundExtractorTest extends TestCase
{
    public function testReadsCorrectly(): void
    {
        $array = ['serialized' => serialize([
            'headline' => 'headline content',
            'text' => 'text content',
        ])];

        $headline = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $text     = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $headline->expects($this->once())->method('name')->willReturn('headline');
        $headline
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function (array $row) {
                return $row['headline'];
            });

        $text->expects($this->once())->method('name')->willReturn('text');
        $text
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function (array $row) {
                return $row['text'];
            });

        $extractor = new SerializingCompoundExtractor('serialized', [$headline, $text]);

        $this->assertSame('serialized', $extractor->name());
        $this->assertTrue($extractor->supports($array));
        $this->assertSame(
            [
                'headline',
                'text',
            ],
            \iterator_to_array($extractor->keys($array))
        );

        $this->assertSame('headline content', $extractor->get('headline', $array));
        $this->assertSame('text content', $extractor->get('text', $array));
    }

    public function testWritesCorrectly(): void
    {
        $array = ['serialized' => serialize([])];

        $headline = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $text     = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $headline->expects($this->once())->method('name')->willReturn('headline');
        $headline
            ->expects($this->once())
            ->method('set')
            ->willReturnCallback(function (array &$row, string $value = null) {
                $row['headline'] = $value;
            });

        $text->expects($this->once())->method('name')->willReturn('text');
        $text
            ->expects($this->once())
            ->method('set')
            ->willReturnCallback(function (array &$row, string $value = null) {
                $row['text'] = $value;
            });

        $extractor = new SerializingCompoundExtractor('serialized', [$headline, $text]);

        $extractor->set('headline', $array, 'headline content');
        $extractor->set('text', $array, 'text content');

        $this->assertSame(['serialized' => serialize([
            'headline' => 'headline content',
            'text' => 'text content',
        ])], $array);
    }
}
