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

use CyberSpectrum\I18N\Contao\Extractor\AbstractConditionalExtractor;
use CyberSpectrum\I18N\Contao\Extractor\Condition\ConditionInterface;
use PHPUnit\Framework\TestCase;

/**
 * This tests the abstract conditional extractor.
 *
 * @covers \CyberSpectrum\I18N\Contao\Extractor\AbstractConditionalExtractor
 */
class AbstractConditionalExtractorTest extends TestCase
{
    /**
     * Test that the condition may be set and is queried.
     *
     * @return void
     */
    public function testFunctionality(): void
    {
        $extractor = $this->getMockForAbstractClass(AbstractConditionalExtractor::class);

        $condition = $this->getMockForAbstractClass(ConditionInterface::class);

        $condition
            ->expects($this->exactly(2))
            ->method('evaluate')
            ->withConsecutive([$row1 = ['row' => 1]], [$row2 = ['row' => 2]])
            ->willReturnOnConsecutiveCalls(true, false);

        $extractor->setCondition($condition);

        $this->assertTrue($extractor->supports($row1));
        $this->assertFalse($extractor->supports($row2));
    }
}
