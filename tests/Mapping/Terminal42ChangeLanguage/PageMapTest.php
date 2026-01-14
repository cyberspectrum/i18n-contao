<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Test\Mapping\Terminal42ChangeLanguage;

use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ContaoDatabase;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\PageMap;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

#[CoversClass(PageMap::class)]
class PageMapTest extends TestCase
{
    #[AllowMockObjectsWithoutExpectations]
    public function testBuildsMapCorrectly(): void
    {
        $database = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger   = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $database->expects($this->once())->method('getRootPages')->willReturn([
            [
                'id'       => 1,
                'language' => 'en',
                'fallback' => '1',
            ],
            [
                'id'       => 100,
                'language' => 'de',
                'fallback' => '',
            ],
            [
                'id'       => 1000,
                'language' => 'fr',
                'fallback' => '',
            ],
        ]);

        $database
            ->expects($this->exactly(4))
            ->method('getPagesByPidList')
            ->willReturnCallback(
                static function (array $pidList): array {
                    static $invocation = 0;
                    switch ($invocation++) {
                        case 0:
                            // Source lookups
                            self::assertSame([100], $pidList);
                            return [
                                [
                                    'id' => 200,
                                    'pid' => 100,
                                    'languageMain' => 2,
                                    'type' => 'regular',
                                ],
                            ];
                        case 1:
                            self::assertSame([200], $pidList);
                            return [];
                        case 2:
                            // Target lookups
                            self::assertSame([1000], $pidList);
                            return [
                                [
                                    'id'           => 2000,
                                    'pid'          => 1000,
                                    'languageMain' => 2,
                                    'type'         => 'regular',
                                ],
                            ];
                        case 3:
                            self::assertSame([2000], $pidList);
                            return [];
                        default:
                            throw new RuntimeException('Unexpected invocation');
                    }
                }
            );

        $map = new PageMap('de', 'fr', $database, $logger);

        $this->assertSame(1000, $map->getTargetIdFor(100));
        $this->assertSame(100, $map->getSourceIdFor(1000));
        $this->assertSame(1, $map->getMainFromSource(100));
        $this->assertSame(1, $map->getMainFromTarget(1000));
        $this->assertSame('root', $map->getTypeFor(1));
        $this->assertSame('root', $map->getTypeFor(100));
        $this->assertSame('root', $map->getTypeFor(1000));

        $this->assertSame('regular', $map->getTypeFor(200));
        $this->assertSame('regular', $map->getTypeFor(2000));

        // Unknown page.
        $this->assertSame('unknown', $map->getTypeFor(0));
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testBuildsMapUsingLookupFallback(): void
    {
        $database = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger   = $this->getMockBuilder(LoggerInterface::class)->getMock();

        $database->expects($this->once())->method('getRootPages')->willReturn([
            [
                'id'       => 1,
                'language' => 'en',
                'fallback' => '1',
            ],
            [
                'id'       => 100,
                'language' => 'de',
                'fallback' => '',
            ],
            [
                'id'       => 1000,
                'language' => 'fr',
                'fallback' => '',
            ],
        ]);

        $database
            ->expects($this->exactly(5))
            ->method('getPagesByPidList')

            ->willReturnCallback(
                static function (array $pidList): array {
                    static $invocation = 0;
                    switch ($invocation++) {
                        case 0:
                            // Source lookup
                            self::assertSame([100], $pidList);
                            return [
                                [
                                    'id' => 200,
                                    'pid' => 100,
                                    'languageMain' => null,
                                    'type' => 'regular',
                                ],
                            ];
                        case 1:
                            // Fallback lookup
                            self::assertSame([1], $pidList);
                            return [
                                [
                                    'id'           => 2,
                                    'pid'          => 1,
                                    'languageMain' => null,
                                    'type'         => 'regular',
                                ],
                            ];
                        case 2:
                            // Source lookup
                            self::assertSame([200], $pidList);
                            return [];
                        case 3:
                            // Target lookups
                            self::assertSame([1000], $pidList);
                            return  [
                                [
                                    'id'           => 2000,
                                    'pid'          => 1000,
                                    'languageMain' => 2,
                                    'type'         => 'regular',
                                ],
                            ];
                        case 4:
                            self::assertSame([2000], $pidList);
                            return [];
                        default:
                            throw new RuntimeException('Unexpected invocation');
                    }
                }
            );

        $pageMap = new PageMap('de', 'fr', $database, $logger);

        $this->assertSame(1000, $pageMap->getTargetIdFor(100));
        $this->assertSame(100, $pageMap->getSourceIdFor(1000));
        $this->assertSame(1, $pageMap->getMainFromSource(100));
        $this->assertSame(1, $pageMap->getMainFromTarget(1000));
        $this->assertSame('root', $pageMap->getTypeFor(1));
        $this->assertSame('root', $pageMap->getTypeFor(100));
        $this->assertSame('root', $pageMap->getTypeFor(1000));

        $this->assertSame('regular', $pageMap->getTypeFor(200));
        $this->assertSame('regular', $pageMap->getTypeFor(2000));

        // Unknown page.
        $this->assertSame('unknown', $pageMap->getTypeFor(0));
    }
}
