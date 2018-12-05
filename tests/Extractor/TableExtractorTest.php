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

use CyberSpectrum\I18N\Contao\Extractor\TableExtractor;
use PHPUnit\Framework\TestCase;

/**
 * This tests the array extractor.
 *
 * @covers \CyberSpectrum\I18N\Contao\Extractor\TableExtractor
 */
class TableExtractorTest extends TestCase
{
    /**
     * Test.
     *
     * @return void
     */
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

    /**
     * Test.
     *
     * @return void
     */
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
