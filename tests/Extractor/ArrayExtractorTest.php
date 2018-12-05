<?php

/**
 * This file is part of cyberspectrum/i18n-contao.
 *
 * (c) 2018 CyberSpectrum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    cyberspectrum/i18n-contao
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2018 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/i18n-contao/blob/master/LICENSE MIT
 * @filesource
 */

declare(strict_types = 1);

namespace CyberSpectrum\I18N\Contao\Test\Extractor;

use CyberSpectrum\I18N\Contao\Extractor\ArrayExtractor;
use CyberSpectrum\I18N\Contao\Extractor\StringExtractorInterface;
use PHPUnit\Framework\TestCase;

/**
 * This tests the array extractor.
 *
 * @covers \CyberSpectrum\I18N\Contao\Extractor\ArrayExtractor
 */
class ArrayExtractorTest extends TestCase
{
    /**
     * Test.
     *
     * @return void
     */
    public function testOnArray(): void
    {
        $array = [
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
        ];

        $headline = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $text     = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $headline->expects($this->once())->method('name')->willReturn('headline');
        $headline
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (array $row) {
                return $row['headline'];
            });

        $text->expects($this->once())->method('name')->willReturn('text');
        $text
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (array $row) {
                return $row['text'];
            });

        $extractor = new ArrayExtractor('items', [$headline, $text]);

        $this->assertSame('items', $extractor->name());
        $this->assertTrue($extractor->supports($array));
        $this->assertSame(
            [
                '0.headline',
                '0.text',
                '1.headline',
                '1.text',
            ],
            \iterator_to_array($extractor->keys($array))
        );

        $this->assertSame('headline content 1', $extractor->get('0.headline', $array));
        $this->assertSame('text content 1', $extractor->get('0.text', $array));
        $this->assertSame('headline content 2', $extractor->get('1.headline', $array));
        $this->assertSame('text content 2', $extractor->get('1.text', $array));
    }

    /**
     * Test.
     *
     * @return void
     */
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
