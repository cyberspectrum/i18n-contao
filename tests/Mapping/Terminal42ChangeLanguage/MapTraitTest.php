<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Test\Mapping\Terminal42ChangeLanguage;

use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ContaoDatabase;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use Symfony\Component\ErrorHandler\BufferingLogger;

use function iterator_to_array;

/** @covers \CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\MapTrait */
class MapTraitTest extends TestCase
{
    public function testGetters(): void
    {
        $database = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger   = $this->getMockForAbstractClass(LoggerInterface::class);

        $instance = new MapTraitDouble([100 => 1, 200 => 2], [1000 => 1, 2000 => 2], $database, $logger);

        $this->assertSame('fr', $instance->getSourceLanguage());
        $this->assertSame('de', $instance->getTargetLanguage());
        $this->assertSame('en', $instance->getMainLanguage());
        $this->assertSame([100, 200], iterator_to_array($instance->sourceIds()));
        $this->assertSame([1000, 2000], iterator_to_array($instance->targetIds()));
        $this->assertTrue($instance->hasTargetFor(100));
        $this->assertFalse($instance->hasTargetFor(101));
        $this->assertSame(1000, $instance->getTargetIdFor(100));
        $this->assertSame(100, $instance->getSourceIdFor(1000));
        $this->assertSame(1, $instance->getMainFromSource(100));
        $this->assertSame(1, $instance->getMainFromTarget(1000));
        $this->assertSame($database, $instance->getDatabase());
    }

    public function testGetTargetIdForThrowsExceptionWhenUnmapped(): void
    {
        $database = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger   = $this->getMockForAbstractClass(LoggerInterface::class);
        $instance = new MapTraitDouble([], [], $database, $logger);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not mapped');

        $instance->getTargetIdFor(1);
    }

    public function testGetSourceIdForThrowsExceptionWhenUnmapped(): void
    {
        $database = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger   = $this->getMockForAbstractClass(LoggerInterface::class);
        $instance = new MapTraitDouble([], [], $database, $logger);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not mapped');

        $instance->getSourceIdFor(1);
    }

    public function testMainFromSourceThrowsExceptionWhenUnmapped(): void
    {
        $database = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger   = $this->getMockForAbstractClass(LoggerInterface::class);
        $instance = new MapTraitDouble([], [], $database, $logger);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not mapped');

        $instance->getMainFromSource(1);
    }

    public function testGetMainFromTargetThrowsExceptionWhenUnmapped(): void
    {
        $database = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger   = $this->getMockForAbstractClass(LoggerInterface::class);
        $instance = new MapTraitDouble([], [], $database, $logger);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Not mapped');

        $instance->getMainFromTarget(1);
    }

    /**
     * Test provider for the 'analyze' method.
     *
     * @return array
     */
    public function analyzeProvider(): array
    {
        return [
            'Good mapping' => [
                'expected' => [],
                'sourceMapping' => [
                    100 => 1,
                ],
                'targetMapping' => [
                    1000 => 1,
                ],
            ],
            'Double mapping from source to main.' => [
                'expected' => [
                    [
                        'language'       => 'fr',
                        'mainLanguage'   => 'en',
                        'mainId'         => 1,
                        'multiple'       => [101, 103],
                        'msg_type'       => 'multiple_mapping_in_source',
                        'class'          => MapTraitDouble::class,
                    ]
                ],
                'sourceMapping' => [
                    101 => 1,
                    102 => 2,
                    103 => 1,
                ],
                'targetMapping' => [
                    1001 => 1,
                    1002 => 2,
                ],
            ],
            'Double mapping from target to main.' => [
                'expected' => [
                    [
                        'language'       => 'de',
                        'mainLanguage'   => 'en',
                        'mainId'         => 1,
                        'multiple'       => [1001, 1003],
                        'msg_type'       => 'multiple_mapping_in_target',
                        'class'          => MapTraitDouble::class,
                    ]
                ],
                'sourceMapping' => [
                    101 => 1,
                    102 => 2,
                ],
                'targetMapping' => [
                    1001 => 1,
                    1002 => 2,
                    1003 => 1,
                ],
            ],
            'Missing source for target.' => [
                'expected' => [
                    [
                        'sourceLanguage' => 'fr',
                        'mainLanguage'   => 'en',
                        'targetLanguage' => 'de',
                        'targetId'       => 1002,
                        'mainId'         => 2,
                        'msg_type'       => 'no_source_for_target',
                        'class'          => MapTraitDouble::class,
                    ]
                ],
                'sourceMapping' => [
                    101 => 1,
                ],
                'targetMapping' => [
                    1001 => 1,
                    1002 => 2,
                ],
            ],
        ];
    }

    /**
     * Test that analyzing the compiled map reports the correct errors.
     *
     * @param array $expected      The expected errors.
     * @param array $sourceMapping The mapping to check.
     * @param array $targetMapping The mapping to check.
     *
     * @return void
     *
     * @dataProvider analyzeProvider
     */
    public function testAnalyzeReportsKnownErrors(array $expected, array $sourceMapping, array $targetMapping): void
    {
        $database = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger   = new BufferingLogger();

        new MapTraitDouble($sourceMapping, $targetMapping, $database, $logger);

        // We only analyze the error context.
        $output = [];
        foreach ($logger->cleanLogs() as $item) {
            $output[] = $item[2];
        }

        $this->assertSame($expected, $output);
    }
}
