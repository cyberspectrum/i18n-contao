<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Test\Mapping\Terminal42ChangeLanguage;

use ArrayIterator;
use Closure;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ArticleMap;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ContaoDatabase;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\PageMap;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/** @covers \CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ArticleMap */
class ArticleMapTest extends TestCase
{
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

        $logger   = $this->getMockForAbstractClass(LoggerInterface::class);
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
            ->withConsecutive([101], [102])
            ->willReturn(1, 2);

        $pageMap->expects($this->once())->method('targetIds')->willReturn(new ArrayIterator([1001, 1002]));
        $pageMap
            ->expects($this->exactly(2))
            ->method('getMainFromTarget')
            ->withConsecutive([1001], [1002])
            ->willReturn(1, 2);
        Closure::fromCallable(function () {
            $this->mainLanguage = 'en';
        })->bindTo($pageMap, PageMap::class)->__invoke();

        $database
            ->expects($this->exactly(4))
            ->method('getArticlesByPid')
            ->withConsecutive([101], [102], [1001], [1002])
            ->willReturn(
                [
                    [
                        'id'           => 101,
                        'pid'          => 101,
                        'inColumn'     => 'main',
                        'languageMain' => 1,
                    ],
                ],
                [
                    [
                        'id'           => 102,
                        'pid'          => 102,
                        'inColumn'     => 'main',
                        'languageMain' => 2,
                    ],
                ],
                [
                    [
                        'id'           => 1001,
                        'pid'          => 1001,
                        'inColumn'     => 'main',
                        'languageMain' => 1,
                    ],
                ],
                [
                    [
                        'id'           => 1002,
                        'pid'          => 1002,
                        'inColumn'     => 'main',
                        'languageMain' => 2,
                    ],
                ]
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
