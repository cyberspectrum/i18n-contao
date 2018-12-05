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

use CyberSpectrum\I18N\Contao\Extractor\TextExtractor;
use PHPUnit\Framework\TestCase;

/**
 * This tests the array extractor.
 *
 * @covers \CyberSpectrum\I18N\Contao\Extractor\TextExtractor
 */
class TextExtractorTest extends TestCase
{
    /**
     * Test.
     *
     * @return void
     */
    public function testReadsCorrectly(): void
    {
        $array = ['item' => 'text'];

        $extractor = new TextExtractor('item');

        $this->assertSame('item', $extractor->name());
        $this->assertTrue($extractor->supports($array));
        $this->assertSame('text', $extractor->get($array));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testWritesCorrectly(): void
    {
        $array = ['item' => null];

        $extractor = new TextExtractor('item');

        $extractor->set($array, 'text');

        $this->assertSame(['item' => 'text'], $array);
    }
}
