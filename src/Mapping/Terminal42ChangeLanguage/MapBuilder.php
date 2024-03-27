<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage;

use CyberSpectrum\I18N\Contao\Mapping\MapBuilderInterface;
use CyberSpectrum\I18N\Contao\Mapping\MappingInterface;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

use function array_key_exists;
use function in_array;

/**
 * This provides the table mappings when using changelanguage by terminal42.
 */
class MapBuilder implements MapBuilderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** The database. */
    private ContaoDatabase $database;

    /**
     * The page map.
     *
     * @var array<string, PageMap>
     */
    private array $pageMaps = [];

    /**
     * The article map.
     *
     * @var array<string, ArticleMap>
     */
    private array $articleMaps = [];

    /**
     * The article map.
     *
     * @var array<string, ArticleContentMap>
     */
    private array $articleContentMaps = [];

    /**
     * The supported language keys.
     *
     * @var list<string>
     */
    private ?array $supportedLanguages = null;

    /**
     * Create a new instance.
     *
     * @param ContaoDatabase $database The database.
     */
    public function __construct(ContaoDatabase $database)
    {
        $this->database = $database;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException When the table is unknown.
     */
    public function getMappingFor(string $tables, string $sourceLanguage, string $targetLanguage): MappingInterface
    {
        switch ($tables) {
            case 'tl_page':
                return $this->getPageMap($sourceLanguage, $targetLanguage);
            case 'tl_article':
                return $this->getArticleMap($sourceLanguage, $targetLanguage);
            case 'tl_article.tl_content':
                return $this->getArticleContentMap($sourceLanguage, $targetLanguage);
            default:
        }
        throw new InvalidArgumentException('Unknown table ' . $tables);
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $tablePath, string $sourceLanguage, string $targetLanguage): bool
    {
        return in_array($tablePath, ['tl_page', 'tl_article', 'tl_article.tl_content'])
            && $this->supportsLanguage($sourceLanguage)
            && $this->supportsLanguage($targetLanguage);
    }

    /**
     * {@inheritDoc}
     */
    public function getSupportedLanguages(): array
    {
        if (null === $this->supportedLanguages) {
            $this->supportedLanguages = \array_map(function (array $row) {
                return $row['language'];
            }, $this->database->getRootPages());
        }

        return $this->supportedLanguages;
    }

    /**
     * Retrieve pageMap.
     *
     * @param string $sourceLanguage The source language.
     * @param string $targetLanguage The target language.
     *
     * @return PageMap
     */
    private function getPageMap(string $sourceLanguage, string $targetLanguage): PageMap
    {
        if (!array_key_exists($key = $sourceLanguage . '->' . $targetLanguage, $this->pageMaps)) {
            return $this->pageMaps[$key] = new PageMap(
                $sourceLanguage,
                $targetLanguage,
                $this->database,
                $this->getLogger()
            );
        }

        return $this->pageMaps[$key];
    }

    /**
     * Retrieve article map.
     *
     * @param string $sourceLanguage The source language.
     * @param string $targetLanguage The target language.
     *
     * @return ArticleMap
     */
    private function getArticleMap(string $sourceLanguage, string $targetLanguage): ArticleMap
    {
        if (!array_key_exists($key = $sourceLanguage . '->' . $targetLanguage, $this->articleMaps)) {
            return $this->articleMaps[$key] = new ArticleMap(
                $this->getPageMap($sourceLanguage, $targetLanguage),
                $this->getLogger()
            );
        }

        return $this->articleMaps[$key];
    }

    /**
     * Retrieve article content map.
     *
     * @param string $sourceLanguage The source language.
     * @param string $targetLanguage The target language.
     *
     * @return ArticleContentMap
     */
    private function getArticleContentMap(string $sourceLanguage, string $targetLanguage): ArticleContentMap
    {
        if (!array_key_exists($key = $sourceLanguage . '->' . $targetLanguage, $this->articleContentMaps)) {
            return $this->articleContentMaps[$key] = new ArticleContentMap(
                $this->getArticleMap($sourceLanguage, $targetLanguage),
                $this->getLogger()
            );
        }

        return $this->articleContentMaps[$key];
    }

    /**
     * Test if the passed language is supported.
     *
     * @param string $language The language to check.
     *
     * @return bool
     */
    private function supportsLanguage(string $language): bool
    {
        return in_array($language, $this->getSupportedLanguages(), true);
    }

    private function getLogger(): LoggerInterface
    {
        if (null === $this->logger) {
            throw new \RuntimeException('Logger not provided');
        }

        return $this->logger;
    }
}
