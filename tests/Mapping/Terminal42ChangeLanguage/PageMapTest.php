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

namespace CyberSpectrum\I18N\Contao\Test\Mapping\Terminal42ChangeLanguage;

use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ContaoDatabase;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\PageMap;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * This tests the page map.
 *
 * @covers \CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\PageMap
 */
class PageMapTest extends TestCase
{
    /**
     * Test the page map building.
     *
     * @return void
     */
    public function testBuildsMapCorrectly(): void
    {
        $database = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger   = $this->getMockForAbstractClass(LoggerInterface::class);

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
            ->withConsecutive(
                // Source lookups
                [[100]],
                [[200]],
                // Target lookups
                [[1000]],
                [[2000]]
            )
            ->willReturnOnConsecutiveCalls(
                // Source lookups
                [
                    [
                        'id'           => 200,
                        'pid'          => 100,
                        'languageMain' => 2,
                        'type'         => 'regular',
                    ],
                ],
                [],
                // Target lookups
                [
                    [
                        'id'           => 2000,
                        'pid'          => 1000,
                        'languageMain' => 2,
                        'type'         => 'regular',
                    ],
                ],
                []
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

    /**
     * Test the page map building.
     *
     * @return void
     */
    public function testBuildsMapUsingLookupFallback(): void
    {
        $database = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger   = $this->getMockForAbstractClass(LoggerInterface::class);

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
            ->withConsecutive(
                // Source lookup
                [[100]],
                // Fallback lookup
                [[1]],
                // Source lookup
                [[200]],
                // Target lookups
                [[1000]],
                [[2000]]
            )
            ->willReturnOnConsecutiveCalls(
                // Source lookup
                [
                    [
                        'id'           => 200,
                        'pid'          => 100,
                        'languageMain' => null,
                        'type'         => 'regular',
                    ],
                ],
                // Fallback lookup
                [
                    [
                        'id'           => 2,
                        'pid'          => 1,
                        'languageMain' => null,
                        'type'         => 'regular',
                    ],
                ],
                // Source lookup
                [],
                // Target lookups
                [
                    [
                        'id'           => 2000,
                        'pid'          => 1000,
                        'languageMain' => 2,
                        'type'         => 'regular',
                    ],
                ],
                []
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
