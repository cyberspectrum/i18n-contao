<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Test\Extractor;

use CyberSpectrum\I18N\Contao\Extractor\AbstractConditionalExtractor;
use CyberSpectrum\I18N\Contao\Extractor\Condition\ConditionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/** @covers  */
#[CoversClass(AbstractConditionalExtractor::class)]
class AbstractConditionalExtractorTest extends TestCase
{
    public function testFunctionality(): void
    {
        $extractor = $this->getMockBuilder(AbstractConditionalExtractor::class)->onlyMethods(['name'])->getMock();
        $condition = $this->getMockBuilder(ConditionInterface::class)->onlyMethods(['evaluate'])->getMock();

        $condition
            ->expects($this->exactly(2))
            ->method('evaluate')
            ->willReturnCallback(
                static function (array $data): bool {
                    static $invocation = 0;
                    switch ($invocation++) {
                        case 0:
                            self::assertSame(['row' => 1], $data);
                            return true;
                        case 1:
                            self::assertSame(['row' => 2], $data);
                            return false;
                        default:
                            self::fail('Unexpected invocation');
                    }
                }
            );

        $extractor->setCondition($condition);

        $this->assertTrue($extractor->supports(['row' => 1]));
        $this->assertFalse($extractor->supports(['row' => 2]));
    }
}
