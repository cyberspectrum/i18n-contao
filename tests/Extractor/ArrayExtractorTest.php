<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Test\Extractor;

use CyberSpectrum\I18N\Contao\Extractor\ArrayExtractor;
use CyberSpectrum\I18N\Contao\Extractor\StringExtractorInterface;
use PHPUnit\Framework\TestCase;

/** @covers \CyberSpectrum\I18N\Contao\Extractor\ArrayExtractor */
class ArrayExtractorTest extends TestCase
{
    public function testOnArray(): void
    {
        $array = [
            'items' => [
                [
                    'headline' => 'headline content 1',
                    'text' => 'text content 1',
                    'sub' => [
                        [
                            'text' => 'sub text content 1.1',
                        ],
                    ],
                ],
                [
                    'headline' => 'headline content 2',
                    'text' => 'text content 2',
                    'sub' => [
                        [
                            'text' => 'sub text content 2.1',
                        ],
                        [
                            'text' => 'sub text content 2.2',
                        ],
                    ],
                ],
            ],
        ];

        $headline = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $headline->expects($this->once())->method('name')->willReturn('headline');
        $headline
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (array $row) {
                return $row['headline'];
            });

        $text = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $text->expects($this->once())->method('name')->willReturn('text');
        $text
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (array $row) {
                return $row['text'];
            });

        $subText = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $subText->expects($this->once())->method('name')->willReturn('text');
        $subText
            ->expects($this->exactly(3))
            ->method('get')
            ->willReturnCallback(function (array $row) {
                return $row['text'];
            });
        $subExtractor = new ArrayExtractor('sub', [$subText]);

        $extractor = new ArrayExtractor('items', [$headline, $text, $subExtractor]);

        $this->assertSame('items', $extractor->name());
        $this->assertTrue($extractor->supports($array));
        $this->assertSame(
            [
                '0.headline',
                '0.text',
                '0.sub.0.text',
                '1.headline',
                '1.text',
                '1.sub.0.text',
                '1.sub.1.text',
            ],
            \iterator_to_array($extractor->keys($array))
        );

        $this->assertSame('headline content 1', $extractor->get('0.headline', $array));
        $this->assertSame('text content 1', $extractor->get('0.text', $array));
        $this->assertSame('sub text content 1.1', $extractor->get('0.sub.0.text', $array));
        $this->assertSame('headline content 2', $extractor->get('1.headline', $array));
        $this->assertSame('text content 2', $extractor->get('1.text', $array));
        $this->assertSame('sub text content 2.1', $extractor->get('1.sub.0.text', $array));
        $this->assertSame('sub text content 2.2', $extractor->get('1.sub.1.text', $array));
    }

    public function testSetOnArray(): void
    {
        $array = [
            'items' => [
            ],
        ];

        $headline = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $text     = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $headline->expects($this->once())->method('name')->willReturn('headline');
        $headline
            ->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function (array &$row, string $value = null) {
                $row['headline'] = $value;
            });

        $text->expects($this->once())->method('name')->willReturn('text');
        $text
            ->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function (array &$row, string $value = null) {
                $row['text'] = $value;
            });

        $subText = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $subText->expects($this->once())->method('name')->willReturn('text');
        $subText
            ->expects($this->exactly(3))
            ->method('set')
            ->willReturnCallback(function (array &$row, string $value = null) {
                $row['text'] = $value;
            });
        $subExtractor = new ArrayExtractor('sub', [$subText]);

        $extractor = new ArrayExtractor('items', [$headline, $text, $subExtractor]);

        $extractor->set('0.headline', $array, 'headline content 1');
        $extractor->set('0.text', $array, 'text content 1');
        $extractor->set('0.sub.0.text', $array, 'sub text content 1.1');
        $extractor->set('1.headline', $array, 'headline content 2');
        $extractor->set('1.text', $array, 'text content 2');
        $extractor->set('1.sub.0.text', $array, 'sub text content 2.1');
        $extractor->set('1.sub.1.text', $array, 'sub text content 2.2');

        $this->assertSame([
            'items' => [
                [
                    'headline' => 'headline content 1',
                    'text' => 'text content 1',
                    'sub' => [
                        [
                            'text' => 'sub text content 1.1',
                        ],
                    ],
                ],
                [
                    'headline' => 'headline content 2',
                    'text' => 'text content 2',
                    'sub' => [
                        [
                            'text' => 'sub text content 2.1',
                        ],
                        [
                            'text' => 'sub text content 2.2',
                        ],
                    ],
                ],
            ],
        ], $array);
    }

    public function testSetOnEmpty(): void
    {
        $array = [];

        $headline = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $text     = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $headline->expects($this->once())->method('name')->willReturn('headline');
        $headline
            ->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function (array &$row, string $value = null) {
                $row['headline'] = $value;
            });

        $text->expects($this->once())->method('name')->willReturn('text');
        $text
            ->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function (array &$row, string $value = null) {
                $row['text'] = $value;
            });

        $extractor = new ArrayExtractor('items', [$headline, $text]);

        $extractor->set('0.headline', $array, 'headline content 1');
        $extractor->set('0.text', $array, 'text content 1');
        $extractor->set('1.headline', $array, 'headline content 2');
        $extractor->set('1.text', $array, 'text content 2');

        $this->assertSame([
            'items' => [
                [
                    'headline' => 'headline content 1',
                    'text' => 'text content 1',
                ],
                [
                    'headline' => 'headline content 2',
                    'text' => 'text content 2',
                ],
            ],
        ], $array);
    }
}
