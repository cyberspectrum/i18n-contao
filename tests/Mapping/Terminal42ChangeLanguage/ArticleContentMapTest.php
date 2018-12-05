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

use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ArticleContentMap;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ContaoDatabase;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ArticleMap;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * This tests the article map.
 *
 * @covers \CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ArticleContentMap
 */
class ArticleContentMapTest extends TestCase
{
    /**
     * Test the map building.
     *
     * @return void
     */
    public function testBuildsMapCorrectly(): void
    {
        $database   = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger     = $this->getMockForAbstractClass(LoggerInterface::class);
        $articleMap = $this
            ->getMockBuilder(ArticleMap::class)
            ->setMethods([
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

        $articleMap->expects($this->once())->method('targetIds')->willReturn(new \ArrayIterator([1001, 1002]));
        $articleMap
            ->expects($this->exactly(2))
            ->method('getMainFromTarget')
            ->withConsecutive([1001], [1002])
            ->willReturn(1, 2);

        $articleMap
            ->expects($this->exactly(2))
            ->method('getSourceIdFor')
            ->withConsecutive([1001], [1002])
            ->willReturn(101, 102);

        $database
            ->expects($this->exactly(6))
            ->method('getContentByPidFrom')
            ->withConsecutive([1001], [101], [1], [1002], [102], [2])
            ->willReturn(
                [
                    [
                        'id'   => 1001,
                        'type' => 'text',
                    ],
                ],
                [
                    [
                        'id'   => 101,
                        'type' => 'text',
                    ],
                ],
                [
                    [
                        'id'   => 1,
                        'type' => 'text',
                    ],
                ],
                [
                    [
                        'id'   => 1002,
                        'type' => 'text',
                    ],
                ],
                [
                    [
                        'id'   => 102,
                        'type' => 'text',
                    ],
                ],
                [
                    [
                        'id'   => 2,
                        'type' => 'text',
                    ],
                ]
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

    /**
     * Test the map building.
     *
     * @return void
     */
    public function testSkipsForUnknownMain(): void
    {
        $database   = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger     = $this->getMockForAbstractClass(LoggerInterface::class);
        $articleMap = $this
            ->getMockBuilder(ArticleMap::class)
            ->setMethods([
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

        $articleMap->expects($this->once())->method('targetIds')->willReturn(new \ArrayIterator([1001]));
        $articleMap->expects($this->once())->method('getMainFromTarget')->withConsecutive([1001])->willReturn(1);
        $articleMap->expects($this->once())->method('getSourceIdFor')->withConsecutive([1001])->willReturn(101);

        $database
            ->expects($this->exactly(3))
            ->method('getContentByPidFrom')
            ->withConsecutive([1001], [101], [1])
            ->willReturn(
                // Target
                [
                    [
                        'id'   => 1001,
                        'type' => 'text',
                    ],
                ],
                // Source
                [
                    [
                        'id'   => 101,
                        'type' => 'text',
                    ],
                ],
                // Main
                []
            );

        $map = new ArticleContentMap($articleMap, $logger);
        $this->assertSame([], \iterator_to_array($map->sourceIds()));
        $this->assertSame([], \iterator_to_array($map->targetIds()));
    }

    /**
     * Test the map building.
     *
     * @return void
     */
    public function testIgnoresDifferentTypeInSource(): void
    {
        $database   = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger     = $this->getMockForAbstractClass(LoggerInterface::class);
        $articleMap = $this
            ->getMockBuilder(ArticleMap::class)
            ->setMethods([
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

        $articleMap->expects($this->once())->method('targetIds')->willReturn(new \ArrayIterator([1001]));
        $articleMap->expects($this->once())->method('getMainFromTarget')->withConsecutive([1001])->willReturn(1);
        $articleMap->expects($this->once())->method('getSourceIdFor')->withConsecutive([1001])->willReturn(101);

        $database
            ->expects($this->exactly(3))
            ->method('getContentByPidFrom')
            ->withConsecutive([1001], [101], [1])
            ->willReturn(
                // Target
                [
                    [
                        'id'   => 1001,
                        'type' => 'text',
                    ],
                ],
                // Source
                [
                    [
                        'id'   => 101,
                        'type' => 'headline',
                    ],
                ],
                // Main
                [
                    [
                        'id'   => 1,
                        'type' => 'text',
                    ],
                ]
            );

        $map = new ArticleContentMap($articleMap, $logger);
        $this->assertSame([], \iterator_to_array($map->sourceIds()));
        $this->assertSame([], \iterator_to_array($map->targetIds()));
    }

    /**
     * Test the map building.
     *
     * @return void
     */
    public function testIgnoresDifferentTypeInTarget(): void
    {
        $database   = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger     = $this->getMockForAbstractClass(LoggerInterface::class);
        $articleMap = $this
            ->getMockBuilder(ArticleMap::class)
            ->setMethods([
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

        $articleMap->expects($this->once())->method('targetIds')->willReturn(new \ArrayIterator([1001]));
        $articleMap->expects($this->once())->method('getMainFromTarget')->withConsecutive([1001])->willReturn(1);
        $articleMap->expects($this->once())->method('getSourceIdFor')->withConsecutive([1001])->willReturn(101);

        $database
            ->expects($this->exactly(3))
            ->method('getContentByPidFrom')
            ->withConsecutive([1001], [101], [1])
            ->willReturn(
                // Target
                [
                    [
                        'id'   => 1001,
                        'type' => 'headline',
                    ],
                ],
                // Source
                [
                    [
                        'id'   => 101,
                        'type' => 'text',
                    ],
                ],
                // Main
                [
                    [
                        'id'   => 1,
                        'type' => 'text',
                    ],
                ]
            );

        $map = new ArticleContentMap($articleMap, $logger);
        $this->assertSame([], \iterator_to_array($map->sourceIds()));
        $this->assertSame([], \iterator_to_array($map->targetIds()));
    }

    /**
     * Test the map building.
     *
     * @return void
     */
    public function testIgnoresDifferentTypeInMain(): void
    {
        $database   = $this->getMockBuilder(ContaoDatabase::class)->disableOriginalConstructor()->getMock();
        $logger     = $this->getMockForAbstractClass(LoggerInterface::class);
        $articleMap = $this
            ->getMockBuilder(ArticleMap::class)
            ->setMethods([
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

        $articleMap->expects($this->once())->method('targetIds')->willReturn(new \ArrayIterator([1001]));
        $articleMap->expects($this->once())->method('getMainFromTarget')->withConsecutive([1001])->willReturn(1);
        $articleMap->expects($this->once())->method('getSourceIdFor')->withConsecutive([1001])->willReturn(101);

        $database
            ->expects($this->exactly(3))
            ->method('getContentByPidFrom')
            ->withConsecutive([1001], [101], [1])
            ->willReturn(
                // Target
                [
                    [
                        'id'   => 1001,
                        'type' => 'text',
                    ],
                ],
                // Source
                [
                    [
                        'id'   => 101,
                        'type' => 'text',
                    ],
                ],
                // Main
                [
                    [
                        'id'   => 1,
                        'type' => 'headline',
                    ],
                ]
            );

        $map = new ArticleContentMap($articleMap, $logger);
        $this->assertSame([], \iterator_to_array($map->sourceIds()));
        $this->assertSame([], \iterator_to_array($map->targetIds()));
    }
}
