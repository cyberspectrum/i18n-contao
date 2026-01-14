<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Test\Mapping\Terminal42ChangeLanguage;

use ArrayIterator;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ArticleContentMap;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ContaoDatabase;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ArticleMap;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function iterator_to_array;

#[CoversClass(ArticleContentMap::class)]
class ArticleContentMapTest extends TestCase
{
    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testBuildsMapCorrectly(): void
    {
        $database   = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger     = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $articleMap = $this
            ->getMockBuilder(ArticleMap::class)
            ->onlyMethods([
                'getSourceLanguage',
                'getTargetLanguage',
                'getMainLanguage',
                'getDatabase',
                'targetIds',
                'getMainFromTarget',
                'getSourceIdFor'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $articleMap->expects($this->once())->method('getSourceLanguage')->willReturn('de');
        $articleMap->expects($this->once())->method('getTargetLanguage')->willReturn('fr');
        $articleMap->expects($this->once())->method('getMainLanguage')->willReturn('en');
        $articleMap->expects($this->once())->method('getDatabase')->willReturn($database);
        $articleMap->expects($this->once())->method('targetIds')->willReturn(new ArrayIterator([1001, 1002]));
        $articleMap
            ->expects($this->exactly(2))
            ->method('getMainFromTarget')
            ->willReturnCallback(
                static function (int $targetId): int {
                    static $invocation = 0;
                    switch ($invocation++) {
                        case 0:
                            self::assertSame(1001, $targetId);
                            return 1;
                        case 1:
                            self::assertSame(1002, $targetId);
                            return 2;
                        default:
                            throw new RuntimeException('Unexpected invocation');
                    }
                }
            );

        $articleMap
            ->expects($this->exactly(2))
            ->method('getSourceIdFor')
            ->willReturnCallback(
                static function (int $targetId): int {
                    static $invocation = 0;
                    switch ($invocation++) {
                        case 0:
                            self::assertSame(1001, $targetId);
                            return 101;
                        case 1:
                            self::assertSame(1002, $targetId);
                            return 102;
                        default:
                            throw new RuntimeException('Unexpected invocation');
                    }
                }
            );

        $database
            ->expects($this->exactly(6))
            ->method('getContentByPidFrom')
            ->willReturnCallback(
                static function (int $articleId): array {
                    static $invocation = 0;
                    switch ($invocation++) {
                        case 0:
                            self::assertSame(1001, $articleId);
                            return [[
                                'id'   => 1001,
                                'type' => 'text',
                            ]];
                        case 1:
                            self::assertSame(101, $articleId);
                            return [[
                                'id'   => 101,
                                'type' => 'text',
                            ]];
                        case 2:
                            self::assertSame(1, $articleId);
                            return [[
                                'id'   => 1,
                                'type' => 'text',
                            ]];
                        case 3:
                            self::assertSame(1002, $articleId);
                            return [[
                                'id'   => 1002,
                                'type' => 'text',
                            ]];
                        case 4:
                            self::assertSame(102, $articleId);
                            return [[
                                'id'   => 102,
                                'type' => 'text',
                            ]];
                        case 5:
                            self::assertSame(2, $articleId);
                            return [[
                                'id'   => 2,
                                'type' => 'text',
                            ]];
                        default:
                            throw new RuntimeException('Unexpected invocation');
                    }
                }
            );

        $map = new ArticleContentMap($articleMap, $logger);

        $this->assertSame(1001, $map->getTargetIdFor(101));
        $this->assertSame(1002, $map->getTargetIdFor(102));
        $this->assertSame(101, $map->getSourceIdFor(1001));
        $this->assertSame(102, $map->getSourceIdFor(1002));
        $this->assertSame(1, $map->getMainFromSource(101));
        $this->assertSame(2, $map->getMainFromSource(102));
        $this->assertSame(1, $map->getMainFromTarget(1001));
        $this->assertSame(2, $map->getMainFromTarget(1002));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSkipsForUnknownMain(): void
    {
        $database   = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger     = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $articleMap = $this
            ->getMockBuilder(ArticleMap::class)
            ->onlyMethods([
                'getSourceLanguage',
                'getTargetLanguage',
                'getMainLanguage',
                'getDatabase',
                'targetIds',
                'getMainFromTarget',
                'getSourceIdFor'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $articleMap->expects($this->once())->method('getSourceLanguage')->willReturn('de');
        $articleMap->expects($this->once())->method('getTargetLanguage')->willReturn('fr');
        $articleMap->expects($this->once())->method('getMainLanguage')->willReturn('en');
        $articleMap->expects($this->once())->method('getDatabase')->willReturn($database);

        $articleMap->expects($this->once())->method('targetIds')->willReturn(new ArrayIterator([1001]));
        $articleMap->expects($this->once())->method('getMainFromTarget')->with(1001)->willReturn(1);
        $articleMap->expects($this->once())->method('getSourceIdFor')->with(1001)->willReturn(101);

        $database
            ->expects($this->exactly(3))
            ->method('getContentByPidFrom')
            ->willReturnCallback(
                static function (int $articleId): array {
                    static $invocation = 0;
                    switch ($invocation++) {
                        case 0:
                            // Target
                            self::assertSame(1001, $articleId);
                            return [[
                                'id' => 1001,
                                'type' => 'text',
                            ]];
                        case 1:
                            // Source
                            self::assertSame(101, $articleId);
                            return [[
                                'id'   => 101,
                                'type' => 'text',
                            ]];
                        case 2:
                            // Main
                            self::assertSame(1, $articleId);
                            return [];
                        default:
                            throw new RuntimeException('Unexpected invocation');
                    }
                }
            );

        $map = new ArticleContentMap($articleMap, $logger);
        $this->assertSame([], iterator_to_array($map->sourceIds()));
        $this->assertSame([], iterator_to_array($map->targetIds()));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testIgnoresDifferentTypeInSource(): void
    {
        $database   = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger     = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $articleMap = $this
            ->getMockBuilder(ArticleMap::class)
            ->onlyMethods([
                'getSourceLanguage',
                'getTargetLanguage',
                'getMainLanguage',
                'getDatabase',
                'targetIds',
                'getMainFromTarget',
                'getSourceIdFor'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $articleMap->expects($this->once())->method('getSourceLanguage')->willReturn('de');
        $articleMap->expects($this->once())->method('getTargetLanguage')->willReturn('fr');
        $articleMap->expects($this->once())->method('getMainLanguage')->willReturn('en');
        $articleMap->expects($this->once())->method('getDatabase')->willReturn($database);

        $articleMap->expects($this->once())->method('targetIds')->willReturn(new ArrayIterator([1001]));
        $articleMap->expects($this->once())->method('getMainFromTarget')->with(1001)->willReturn(1);
        $articleMap->expects($this->once())->method('getSourceIdFor')->with(1001)->willReturn(101);

        $database
            ->expects($this->exactly(3))
            ->method('getContentByPidFrom')
            ->willReturnCallback(
                static function (int $articleId): array {
                    static $invocation = 0;
                    switch ($invocation++) {
                        case 0:
                            // Target
                            self::assertSame(1001, $articleId);
                            return [[
                                'id' => 1001,
                                'type' => 'text',
                            ]];
                        case 1:
                            // Source
                            self::assertSame(101, $articleId);
                            return [[
                                'id'   => 101,
                                'type' => 'headline',
                            ]];
                        case 2:
                            // Main
                            self::assertSame(1, $articleId);
                            return [[
                                'id'   => 1,
                                'type' => 'text',
                            ]];
                        default:
                            throw new RuntimeException('Unexpected invocation');
                    }
                }
            );

        $map = new ArticleContentMap($articleMap, $logger);
        $this->assertSame([], iterator_to_array($map->sourceIds()));
        $this->assertSame([], iterator_to_array($map->targetIds()));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testIgnoresDifferentTypeInTarget(): void
    {
        $database   = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger     = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $articleMap = $this
            ->getMockBuilder(ArticleMap::class)
            ->onlyMethods([
                'getSourceLanguage',
                'getTargetLanguage',
                'getMainLanguage',
                'getDatabase',
                'targetIds',
                'getMainFromTarget',
                'getSourceIdFor'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $articleMap->expects($this->once())->method('getSourceLanguage')->willReturn('de');
        $articleMap->expects($this->once())->method('getTargetLanguage')->willReturn('fr');
        $articleMap->expects($this->once())->method('getMainLanguage')->willReturn('en');
        $articleMap->expects($this->once())->method('getDatabase')->willReturn($database);

        $articleMap->expects($this->once())->method('targetIds')->willReturn(new ArrayIterator([1001]));
        $articleMap->expects($this->once())->method('getMainFromTarget')->with(1001)->willReturn(1);
        $articleMap->expects($this->once())->method('getSourceIdFor')->with(1001)->willReturn(101);

        $database
            ->expects($this->exactly(3))
            ->method('getContentByPidFrom')
            ->willReturnCallback(
                static function (int $articleId): array {
                    static $invocation = 0;
                    switch ($invocation++) {
                        case 0:
                            // Target
                            self::assertSame(1001, $articleId);
                            return [[
                                'id' => 1001,
                                'type' => 'headline',
                            ]];
                        case 1:
                            // Source
                            self::assertSame(101, $articleId);
                            return [[
                                'id'   => 101,
                                'type' => 'text',
                            ]];
                        case 2:
                            // Main
                            self::assertSame(1, $articleId);
                            return [[
                                'id'   => 1,
                                'type' => 'text',
                            ]];
                        default:
                            throw new RuntimeException('Unexpected invocation');
                    }
                }
            );

        $map = new ArticleContentMap($articleMap, $logger);
        $this->assertSame([], iterator_to_array($map->sourceIds()));
        $this->assertSame([], iterator_to_array($map->targetIds()));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testIgnoresDifferentTypeInMain(): void
    {
        $database   = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger     = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $articleMap = $this
            ->getMockBuilder(ArticleMap::class)
            ->onlyMethods([
                'getSourceLanguage',
                'getTargetLanguage',
                'getMainLanguage',
                'getDatabase',
                'targetIds',
                'getMainFromTarget',
                'getSourceIdFor'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $articleMap->expects($this->once())->method('getSourceLanguage')->willReturn('de');
        $articleMap->expects($this->once())->method('getTargetLanguage')->willReturn('fr');
        $articleMap->expects($this->once())->method('getMainLanguage')->willReturn('en');
        $articleMap->expects($this->once())->method('getDatabase')->willReturn($database);

        $articleMap->expects($this->once())->method('targetIds')->willReturn(new ArrayIterator([1001]));
        $articleMap->expects($this->once())->method('getMainFromTarget')->with(1001)->willReturn(1);
        $articleMap->expects($this->once())->method('getSourceIdFor')->with(1001)->willReturn(101);

        $database
            ->expects($this->exactly(3))
            ->method('getContentByPidFrom')
            ->willReturnCallback(
                static function (int $articleId): array {
                    static $invocation = 0;
                    switch ($invocation++) {
                        case 0:
                            // Target
                            self::assertSame(1001, $articleId);
                            return [[
                                'id' => 1001,
                                'type' => 'text',
                            ]];
                        case 1:
                            // Source
                            self::assertSame(101, $articleId);
                            return [[
                                'id'   => 101,
                                'type' => 'text',
                            ]];
                        case 2:
                            // Main
                            self::assertSame(1, $articleId);
                            return [[
                                'id'   => 1,
                                'type' => 'headline',
                            ]];
                        default:
                            throw new RuntimeException('Unexpected invocation');
                    }
                }
            );

        $map = new ArticleContentMap($articleMap, $logger);
        $this->assertSame([], iterator_to_array($map->sourceIds()));
        $this->assertSame([], iterator_to_array($map->targetIds()));
    }
}
