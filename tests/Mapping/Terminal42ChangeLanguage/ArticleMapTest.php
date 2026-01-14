<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Test\Mapping\Terminal42ChangeLanguage;

use ArrayIterator;
use Closure;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ArticleMap;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ContaoDatabase;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\PageMap;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass(ArticleMap::class)]
class ArticleMapTest extends TestCase
{
    /** @SuppressWarnings(PHPMD.ExcessiveMethodLength) */
    #[AllowMockObjectsWithoutExpectations]
    public function testBuildsMapCorrectly(): void
    {
        $database = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $database
            ->expects($this->once())
            ->method('getRootPages')
            ->willReturn([
                ['id' => 101, 'language' => 'de', 'fallback' => '1'],
                ['id' => 102, 'language' => 'fr', 'fallback' => ''],
            ]);

        $logger   = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $pageMap  = $this
            ->getMockBuilder(PageMap::class)
            ->onlyMethods(['sourceIds', 'targetIds', 'getMainFromSource', 'getMainFromTarget'])
            ->setConstructorArgs([
                'de',
                'fr',
                $database,
                $logger
            ])
            ->getMock();
        $pageMap->expects($this->once())->method('sourceIds')->willReturn(new ArrayIterator([101, 102]));
        $pageMap
            ->expects($this->exactly(2))
            ->method('getMainFromSource')
            ->willReturnCallback(
                static function (int $sourceId): int {
                    static $invocation = 0;
                    switch ($invocation++) {
                        case 0:
                            self::assertSame(101, $sourceId);
                            return 1;
                        case 1:
                            self::assertSame(102, $sourceId);
                            return 2;
                        default:
                            throw new RuntimeException('Unexpected invocation');
                    }
                }
            );

        $pageMap->expects($this->once())->method('targetIds')->willReturn(new ArrayIterator([1001, 1002]));
        $pageMap
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
        (function () {
            $this->mainLanguage = 'en';
        })(...)->bindTo($pageMap, PageMap::class)->__invoke();

        $database
            ->expects($this->exactly(4))
            ->method('getArticlesByPid')
            ->willReturnCallback(
                static function (int $pageId): array {
                    static $invocation = 0;
                    switch ($invocation++) {
                        case 0:
                            self::assertSame(101, $pageId);
                            return [[
                                'id'           => 101,
                                'pid'          => 101,
                                'inColumn'     => 'main',
                                'languageMain' => 1,
                            ]];
                        case 1:
                            self::assertSame(102, $pageId);
                            return [[
                                'id'           => 102,
                                'pid'          => 102,
                                'inColumn'     => 'main',
                                'languageMain' => 2,
                            ]];
                        case 2:
                            self::assertSame(1001, $pageId);
                            return [[
                                'id'           => 1001,
                                'pid'          => 1001,
                                'inColumn'     => 'main',
                                'languageMain' => 1,
                            ]];
                        case 3:
                            self::assertSame(1002, $pageId);
                            return [[
                                'id'           => 1002,
                                'pid'          => 1002,
                                'inColumn'     => 'main',
                                'languageMain' => 2,
                            ]];
                        default:
                            throw new RuntimeException('Unexpected invocation');
                    }
                }
            );

        $map = new ArticleMap($pageMap, $logger);

        $this->assertSame(1001, $map->getTargetIdFor(101));
        $this->assertSame(1002, $map->getTargetIdFor(102));
        $this->assertSame(101, $map->getSourceIdFor(1001));
        $this->assertSame(102, $map->getSourceIdFor(1002));
        $this->assertSame(1, $map->getMainFromSource(101));
        $this->assertSame(2, $map->getMainFromSource(102));
        $this->assertSame(1, $map->getMainFromTarget(1001));
        $this->assertSame(2, $map->getMainFromTarget(1002));
    }
}
