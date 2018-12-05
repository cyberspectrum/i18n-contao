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

namespace CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage;

use CyberSpectrum\I18N\Contao\Mapping\MappingInterface;
use Psr\Log\LoggerInterface;

/**
 * This maps article content element ids between a source language and a target language.
 */
class ArticleContentMap implements MappingInterface
{
    use MapTrait;

    /**
     * The article map.
     *
     * @var ArticleMap
     */
    private $articleMap;

    /**
     * Create a new instance.
     *
     * @param ArticleMap      $articleMap The page map.
     * @param LoggerInterface $logger     The logger to use.
     */
    public function __construct(ArticleMap $articleMap, LoggerInterface $logger)
    {
        $this->sourceLanguage = $articleMap->getSourceLanguage();
        $this->targetLanguage = $articleMap->getTargetLanguage();
        $this->mainLanguage   = $articleMap->getMainLanguage();
        $this->logger         = $logger;
        $this->database       = $articleMap->getDatabase();
        $this->articleMap     = $articleMap;
        $this->buildMap();
    }

    /**
     * Build the map.
     *
     * @return void
     */
    private function buildMap(): void
    {
        // Loop over all articles in target language.
        foreach ($this->articleMap->targetIds() as $targetId) {
            $mainId   = $this->articleMap->getMainFromTarget($targetId);
            $sourceId = $this->articleMap->getSourceIdFor($targetId);

            // Now fetch all content elements for source and target.
            $targetElements = $this->database->getContentByPidFrom($targetId, 'tl_article');
            $sourceElements = $this->database->getContentByPidFrom($sourceId, 'tl_article');
            $mainElements   = $this->database->getContentByPidFrom($mainId, 'tl_article');

            $this->mapElements($targetElements, $mainElements, $this->targetMap, $this->targetMapInverse);
            $this->mapElements($sourceElements, $mainElements, $this->sourceMap, $this->sourceMapInverse);
            $this->combineSourceAndTargetMaps();
        }
    }

    /**
     * Map the passed elements.
     *
     * @param array $elements     The elements to map.
     * @param array $mainElements The main elements.
     * @param array $map          The map to store elements to.
     * @param array $inverse      The inverse map.
     *
     * @return void
     */
    private function mapElements(array $elements, array $mainElements, array &$map, array &$inverse): void
    {
        foreach ($elements as $index => $element) {
            $elementId = (int) $element['id'];
            if (!array_key_exists($index, $mainElements)) {
                $this->logger->warning(
                    'Content element {id} has no mapping in main. Element skipped.',
                    [
                        'id' => $elementId,
                        'msg_type' => 'article_content_no_main',
                    ]
                );
                continue;
            }
            $mainElement = $mainElements[$index];
            $mainId      = (int) $mainElement['id'];
            if ($element['type'] !== $mainElement['type']) {
                $this->logger->warning(
                    'Content element {id} has different type as element in main. Element skipped.',
                    [
                        'id' => $elementId,
                        'mainId' => $mainId,

                        'msg_type' => 'article_content_type_mismatch'
                    ]
                );
                continue;
            }

            $map[$elementId]  = $mainId;
            $inverse[$mainId] = $elementId;
        }
    }
}
