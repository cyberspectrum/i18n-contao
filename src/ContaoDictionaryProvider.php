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

namespace CyberSpectrum\I18N\Contao;

use CyberSpectrum\I18N\Contao\Mapping\MapBuilderInterface;
use CyberSpectrum\I18N\Compound\CompoundDictionary;
use CyberSpectrum\I18N\Dictionary\DictionaryInformation;
use CyberSpectrum\I18N\Dictionary\DictionaryInterface;
use CyberSpectrum\I18N\Dictionary\DictionaryProviderInterface;
use CyberSpectrum\I18N\Compound\WritableCompoundDictionary;
use CyberSpectrum\I18N\Dictionary\WritableDictionaryInterface;
use CyberSpectrum\I18N\Dictionary\WritableDictionaryProviderInterface;
use CyberSpectrum\I18N\Exception\DictionaryNotFoundException;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * This provides the Contao dictionaries.
 */
class ContaoDictionaryProvider implements DictionaryProviderInterface, WritableDictionaryProviderInterface
{
    use LoggerAwareTrait;

    public const ALL_TABLES = 'contao';

    /**
     * Connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * The extractor factory.
     *
     * @var ExtractorFactory
     */
    private $extractorFactory;

    /**
     * The mapping builder.
     *
     * @var MapBuilderInterface
     */
    private $mapBuilder;

    /**
     * The meta information.
     *
     * @var array
     */
    private $dictionaryMeta;

    /**
     * Create a new instance.
     *
     * @param Connection          $connection       The database connection.
     * @param ExtractorFactory    $extractorFactory The extractor factory.
     * @param MapBuilderInterface $mapBuilder       The mapping builder.
     * @param array|null          $dictionaryMeta   The dictionary meta information.
     */
    public function __construct(
        Connection $connection,
        ExtractorFactory $extractorFactory,
        MapBuilderInterface $mapBuilder,
        array $dictionaryMeta = null
    ) {
        $this->connection       = $connection;
        $this->extractorFactory = $extractorFactory;
        $this->mapBuilder       = $mapBuilder;
        $this->setLogger(new NullLogger());

        if (empty($dictionaryMeta)) {
            $dictionaryMeta = [
                'tl_page',
                'tl_article',
                [
                    'name'  => 'tl_article_tl_content',
                    'table' => 'tl_content',
                    'map'   => 'tl_article.tl_content',
                ]
            ];
        }
        foreach ($dictionaryMeta as $item) {
            $this->addDictionaryMeta($item);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return \Traversable|DictionaryInformation[]
     */
    public function getAvailableDictionaries(): \Traversable
    {
        yield from $this->getAvailableDictionaryInformation();
    }

    /**
     * {@inheritDoc}
     *
     * @throws DictionaryNotFoundException When the dictionary does not exist.
     */
    public function getDictionary(
        string $name,
        string $sourceLanguage,
        string $targetLanguage,
        array $customData = []
    ): DictionaryInterface {
        $this->logger->debug('Contao: opening dictionary ' . $name);
        if (array_key_exists($name, $this->dictionaryMeta)) {
            $metaData   = $this->dictionaryMeta[$name];
            $dictionary = new ContaoTableDictionary(
                $metaData['table'],
                $sourceLanguage,
                $targetLanguage,
                $this->connection,
                $this->mapBuilder->getMappingFor($metaData['map'], $sourceLanguage, $targetLanguage),
                $this->extractorFactory->getExtractorsForTable($metaData['table'])
            );
            $dictionary->setLogger($this->logger);

            return $dictionary;
        }
        if (static::ALL_TABLES === $name) {
            $dictionary = new CompoundDictionary($sourceLanguage, $targetLanguage);
            foreach (array_keys($this->dictionaryMeta) as $subName) {
                $dictionary->addDictionary($subName, $this->getDictionary(
                    $subName,
                    $sourceLanguage,
                    $targetLanguage,
                    $customData
                ));
            }
            return $dictionary;
        }

        throw new DictionaryNotFoundException($name, $sourceLanguage, $targetLanguage);
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableWritableDictionaries(): \Traversable
    {
        yield from $this->getAvailableDictionaryInformation();
    }

    /**
     * {@inheritDoc}
     *
     * @throws DictionaryNotFoundException When the dictionary does not exist.
     */
    public function getDictionaryForWrite(
        string $name,
        string $sourceLanguage,
        string $targetLanguage,
        array $customData = []
    ): WritableDictionaryInterface {
        $this->logger->debug('Contao: opening writable dictionary ' . $name);
        if (array_key_exists($name, $this->dictionaryMeta)) {
            $metaData   = $this->dictionaryMeta[$name];
            $dictionary = new ContaoTableDictionary(
                $metaData['table'],
                $sourceLanguage,
                $targetLanguage,
                $this->connection,
                $this->mapBuilder->getMappingFor($metaData['map'], $sourceLanguage, $targetLanguage),
                $this->extractorFactory->getExtractorsForTable($metaData['table'])
            );
            $dictionary->setLogger($this->logger);

            return $dictionary;
        }
        if (static::ALL_TABLES === $name) {
            $dictionary = new WritableCompoundDictionary($sourceLanguage, $targetLanguage);
            foreach (array_keys($this->dictionaryMeta) as $subName) {
                $dictionary->addDictionary(
                    $subName,
                    $this->getDictionaryForWrite($subName, $sourceLanguage, $targetLanguage, $customData)
                );
            }
            return $dictionary;
        }

        throw new DictionaryNotFoundException($name, $sourceLanguage, $targetLanguage);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \RuntimeException Creating dictionaries is not supported by this class.
     */
    public function createDictionary(
        string $name,
        string $sourceLanguage,
        string $targetLanguage,
        array $customData = []
    ): WritableDictionaryInterface {
        throw new \RuntimeException('Creating new dictionaries is not supported.');
    }

    /**
     * Add a dictionary meta information.
     *
     * @param array|string $item The meta array or table name if name, table and map are all the same.
     *
     * @return static
     */
    public function addDictionaryMeta($item)
    {
        if (\is_string($item)) {
            $item = [
                'name'  => $item,
                'table' => $item,
                'map'   => $item,
            ];
        }

        $name  = $item['name'];
        $table = ($item['table'] ?? $name);
        $map   = ($item['map'] ?? $name);

        $this->dictionaryMeta[$name] = [
            'table' => $table,
            'map'   => $map,
        ];

        return $this;
    }

    /**
     * Obtain all dictionary information.
     *
     * @return \Traversable|DictionaryInformation[]
     */
    public function getAvailableDictionaryInformation(): \Traversable
    {
        $languages = $this->getContaoLanguages();

        foreach ($languages as $sourceLanguage) {
            foreach ($languages as $targetLanguage) {
                if ($sourceLanguage === $targetLanguage) {
                    continue;
                }
                foreach (array_keys($this->dictionaryMeta) as $dictionary) {
                    yield new DictionaryInformation($dictionary, $sourceLanguage, $targetLanguage);
                }

                yield new DictionaryInformation(static::ALL_TABLES, $sourceLanguage, $targetLanguage);
            }
        }
    }

    /**
     * Fetch all languages from Contao.
     *
     * @return array
     */
    private function getContaoLanguages(): array
    {
        $languages = [];
        foreach ($this->connection->createQueryBuilder()
                     ->select('language', 'id', 'fallback')
                     ->from('tl_page')
                     ->where('type=:type')
                     ->setParameter('type', 'root')
                     ->orderBy('fallback')
                     ->addOrderBy('sorting')
                     ->execute()->fetchAll(\PDO::FETCH_ASSOC) as $root) {
            $language = $root['language'];

            $languages[] = $language;
        }

        return $languages;
    }
}
