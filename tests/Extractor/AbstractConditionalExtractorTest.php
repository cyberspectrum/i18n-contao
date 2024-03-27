<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Test\Extractor;

use CyberSpectrum\I18N\Contao\Extractor\AbstractConditionalExtractor;
use CyberSpectrum\I18N\Contao\Extractor\Condition\ConditionInterface;
use PHPUnit\Framework\TestCase;

/** @covers \CyberSpectrum\I18N\Contao\Extractor\AbstractConditionalExtractor */
class AbstractConditionalExtractorTest extends TestCase
{
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
