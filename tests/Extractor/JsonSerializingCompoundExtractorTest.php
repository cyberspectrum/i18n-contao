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

use CyberSpectrum\I18N\Contao\Extractor\JsonSerializingCompoundExtractor;
use CyberSpectrum\I18N\Contao\Extractor\StringExtractorInterface;
use PHPUnit\Framework\TestCase;

/**
 * This tests the json serializing extractor.
 *
 * @covers \CyberSpectrum\I18N\Contao\Extractor\AbstractSerializingCompoundExtractor
 * @covers \CyberSpectrum\I18N\Contao\Extractor\JsonSerializingCompoundExtractor
 */
class JsonSerializingCompoundExtractorTest extends TestCase
{
    /**
     * Test.
     *
     * @return void
     */
    public function testReadsCorrectly(): void
    {
        $array = ['json' => json_encode([
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

        $extractor = new JsonSerializingCompoundExtractor('json', [$headline, $text]);

        $this->assertSame('json', $extractor->name());
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
    /**
     * Test.
     *
     * @return void
     */
    public function testReadsNullCorrectly(): void
    {
        $array = ['json' => json_encode(null)];

        $headline = $this->getMockForAbstractClass(StringExtractorInterface::class);
        $headline->expects($this->once())->method('name')->willReturn('headline');
        $headline->expects($this->never())->method('get');

        $extractor = new JsonSerializingCompoundExtractor('json', [$headline]);

        $this->assertSame('json', $extractor->name());
        $this->assertTrue($extractor->supports($array));
        $this->assertSame([], \iterator_to_array($extractor->keys($array)));

        $this->assertNull($extractor->get('headline', $array));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testWritesCorrectly(): void
    {
        $array = ['json' => json_encode([], JSON_FORCE_OBJECT)];

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

        $extractor = new JsonSerializingCompoundExtractor('json', [$headline, $text]);

        $extractor->set('headline', $array, 'headline content');
        $extractor->set('text', $array, 'text content');

        $this->assertSame(['json' => json_encode([
            'headline' => 'headline content',
            'text' => 'text content',
        ])], $array);
    }
}
