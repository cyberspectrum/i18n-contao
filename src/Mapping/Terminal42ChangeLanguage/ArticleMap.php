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
 * This maps page ids between a source language and a target language.
 */
class ArticleMap implements MappingInterface
{
    use MapTrait;

    /**
     * The page map.
     *
     * @var PageMap
     */
    private $pageMap;

    /**
     * Create a new instance.
     *
     * @param PageMap         $pageMap The page map.
     * @param LoggerInterface $logger  The logger to use.
     */
    public function __construct(PageMap $pageMap, LoggerInterface $logger)
    {
        $this->sourceLanguage = $pageMap->getSourceLanguage();
        $this->targetLanguage = $pageMap->getTargetLanguage();
        $this->mainLanguage   = $pageMap->getMainLanguage();
        $this->logger         = $logger;
        $this->database       = $pageMap->getDatabase();
        $this->pageMap        = $pageMap;
        $this->buildMap();
    }

    /**
     * Build the map.
     *
     * @return void
     */
    protected function buildMap(): void
    {
        // Fetch articles mapped from source to main.
        foreach ($this->pageMap->sourceIds() as $page) {
            $this->fetchArticlesFrom($page, $this->sourceMap, $this->pageMap->getMainFromSource($page));
        }
        $this->sourceMapInverse = array_flip($this->sourceMap);

        foreach ($this->pageMap->targetIds() as $page) {
            $this->fetchArticlesFrom($page, $this->targetMap, $this->pageMap->getMainFromTarget($page));
        }
        $this->targetMapInverse = array_flip($this->targetMap);

        $this->combineSourceAndTargetMaps();
    }

    /**
     * Fetch articles from a page.
     *
     * @param int   $pageId   The target page id.
     * @param array $map      The map to update.
     * @param int   $mainPage The id of the page in the main language.
     *
     * @return void
     */
    private function fetchArticlesFrom(int $pageId, array &$map, int $mainPage): void
    {
        $this->logger->debug('Mapping articles from page ' . $pageId);

        $articles = $this->database->getArticlesByPid($pageId);

        if (empty($articles)) {
            // Skip non regular pages - these are known to not have articles within.
            if (\in_array(
                $pageType = $this->pageMap->getTypeFor($pageId),
                ['error_403', 'error_404', 'forward', 'redirect', 'root']
            )) {
                return;
            }

            $this->logger->notice(
                'Page {id} has no articles.',
                [
                    'id' => $pageId,
                    'pageType' => $pageType,
                    'msg_type' => 'page_no_articles'
                ]
            );
            return;
        }

        // If the language page and main page are same, we do identity mapping.
        if ($pageId === $mainPage) {
            foreach ($articles as $index => $article) {
                $articleId       = (int) $article['id'];
                $map[$articleId] = $articleId;
            }
            return;
        }

        foreach ($articles as $index => $article) {
            $mainId    = (int) $article['languageMain'];
            $articleId = (int) $article['id'];
            if (empty($mainId)) {
                if (0 === ($mainId = $this->determineMapFor($index, $article['inColumn'], $mainPage))) {
                    $this->logger->warning(
                        'Article {id} in page {page} has no fallback set and unable to determine automatically. ' .
                        'Article skipped.',
                        ['id' => $articleId, 'page' => (int) $article['pid'], 'msg_type' => 'article_no_fallback']
                    );
                    continue;
                }

                $this->logger->warning(
                    'Article {id} (index: {index}) has no fallback set, expect problems, I guess it is {guessed}',
                    [
                        'id' => $articleId,
                        'index' => $index,
                        'guessed' => $mainId,
                        'msg_type' => 'article_fallback_guess'
                    ]
                );
            }

            $map[$articleId] = $mainId;
        }
    }

    /**
     * Determine the mapping of an article by its index in the list of the main page articles.
     *
     * @param int    $index    The index in the list of articles in the page.
     * @param string $column   The column the articles are contained within.
     * @param int    $mainPage The id of the main page.
     *
     * @return int
     */
    protected function determineMapFor(int $index, string $column, int $mainPage): int
    {
        // Lookup all children of page in main language.
        $mainSiblings = $this->database->getArticlesByPid($mainPage, $column);

        return isset($mainSiblings[$index]) ? (int) $mainSiblings[$index]['id'] : 0;
    }
}
