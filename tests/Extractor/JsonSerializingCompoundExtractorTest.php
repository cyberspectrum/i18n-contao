<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Test\Extractor;

use CyberSpectrum\I18N\Contao\Extractor\AbstractSerializingCompoundExtractor;
use CyberSpectrum\I18N\Contao\Extractor\JsonSerializingCompoundExtractor;
use CyberSpectrum\I18N\Contao\Extractor\StringExtractorInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

#[CoversClass(AbstractSerializingCompoundExtractor::class)]
#[CoversClass(JsonSerializingCompoundExtractor::class)]

class JsonSerializingCompoundExtractorTest extends TestCase
{
    public function testReadsCorrectly(): void
    {
        $array = ['json' => json_encode([
            'headline' => 'headline content',
            'text' => 'text content',
        ], JSON_THROW_ON_ERROR)];

        $headline = $this->getMockBuilder(StringExtractorInterface::class)->getMock();
        $text     = $this->getMockBuilder(StringExtractorInterface::class)->getMock();
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

        $extractor = new JsonSerializingCompoundExtractor('json', [$headline, $text]);

        $this->assertSame('json', $extractor->name());
        $this->assertTrue($extractor->supports($array));
        $this->assertSame(
            [
                'headline',
                'text',
            ],
            iterator_to_array($extractor->keys($array))
        );

        $this->assertSame('headline content', $extractor->get('headline', $array));
        $this->assertSame('text content', $extractor->get('text', $array));
    }

    public function testReadsNullCorrectly(): void
    {
        $array = ['json' => json_encode(null)];

        $headline = $this->getMockBuilder(StringExtractorInterface::class)->getMock();
        $headline->expects($this->once())->method('name')->willReturn('headline');
        $headline->expects($this->never())->method('get');

        $extractor = new JsonSerializingCompoundExtractor('json', [$headline]);

        $this->assertSame('json', $extractor->name());
        $this->assertTrue($extractor->supports($array));
        $this->assertSame([], iterator_to_array($extractor->keys($array)));

        $this->assertNull($extractor->get('headline', $array));
    }

    public function testWritesCorrectly(): void
    {
        $array = ['json' => json_encode([], JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT)];

        $headline = $this->getMockBuilder(StringExtractorInterface::class)->getMock();
        $text     = $this->getMockBuilder(StringExtractorInterface::class)->getMock();
        $headline->expects($this->once())->method('name')->willReturn('headline');
        $headline
            ->expects($this->once())
            ->method('set')
            ->willReturnCallback(function (array &$row, ?string $value = null) {
                $row['headline'] = $value;
            });

        $text->expects($this->once())->method('name')->willReturn('text');
        $text
            ->expects($this->once())
            ->method('set')
            ->willReturnCallback(function (array &$row, ?string $value = null) {
                $row['text'] = $value;
            });

        $extractor = new JsonSerializingCompoundExtractor('json', [$headline, $text]);

        $extractor->set('headline', $array, 'headline content');
        $extractor->set('text', $array, 'text content');

        $this->assertSame(['json' => json_encode([
            'headline' => 'headline content',
            'text' => 'text content',
        ], JSON_THROW_ON_ERROR)], $array);
    }
}
