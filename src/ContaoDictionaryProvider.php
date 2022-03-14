<?php

declare(strict_types=1);

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
use InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;
use Traversable;

use function is_string;

/**
 * This provides the Contao dictionaries.
 *
 * @psalm-type TContaoDictionaryMetaDataInput=array{name: string, table: string, map: string}|string
 * @psalm-type TContaoDictionaryMetaData=array{table: string, map: string}
 */
class ContaoDictionaryProvider implements DictionaryProviderInterface, WritableDictionaryProviderInterface
{
    use LoggerAwareTrait;

    public const ALL_TABLES = 'contao';

    /** Connection */
    private Connection $connection;

    /** The extractor factory. */
    private ExtractorFactory $extractorFactory;

    /** The mapping builder. */
    private MapBuilderInterface $mapBuilder;

    /**
     * The meta information.
     *
     * @var array<string, TContaoDictionaryMetaData>
     */
    private array $dictionaryMeta = [];

    /**
     * Create a new instance.
     *
     * @param Connection                                $connection       The database connection.
     * @param ExtractorFactory                          $extractorFactory The extractor factory.
     * @param MapBuilderInterface                       $mapBuilder       The mapping builder.
     * @param list<TContaoDictionaryMetaDataInput>|null $dictionaryMeta   The dictionary meta information.
     */
    public function __construct(
        Connection $connection,
        ExtractorFactory $extractorFactory,
        MapBuilderInterface $mapBuilder,
        ?array $dictionaryMeta
    ) {
        $this->connection       = $connection;
        $this->extractorFactory = $extractorFactory;
        $this->mapBuilder       = $mapBuilder;

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
        $this->checkMeta($dictionaryMeta);

        foreach ($dictionaryMeta as $item) {
            $this->addDictionaryMeta($item);
        }
    }

    public function getAvailableDictionaries(): Traversable
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
        if ($this->logger) {
            $this->logger->debug('Contao: opening dictionary ' . $name);
        }
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
            if ($this->logger) {
                $dictionary->setLogger($this->logger);
            }

            return $dictionary;
        }
        if (self::ALL_TABLES === $name) {
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

    public function getAvailableWritableDictionaries(): Traversable
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
        if ($this->logger) {
            $this->logger->debug('Contao: opening writable dictionary ' . $name);
        }
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
            if ($this->logger) {
                $dictionary->setLogger($this->logger);
            }

            return $dictionary;
        }
        if (self::ALL_TABLES === $name) {
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
     * @throws InvalidArgumentException Creating dictionaries is not supported by this class.
     */
    public function createDictionary(
        string $name,
        string $sourceLanguage,
        string $targetLanguage,
        array $customData = []
    ): WritableDictionaryInterface {
        throw new InvalidArgumentException('Creating new dictionaries is not supported.');
    }

    /**
     * Add a dictionary meta information.
     *
     * @param array|string $item The meta array or table name if name, table and map are all the same.
     */
    public function addDictionaryMeta($item): void
    {
        if (is_string($item)) {
            $item = [
                'name'  => $item,
                'table' => $item,
                'map'   => $item,
            ];
        }

        $name = $item['name'] ?? null;
        if (!is_string($name)) {
            throw new InvalidArgumentException('Name must be a string.');
        }

        $table = $item['table'] ?? $name;
        if (!is_string($table)) {
            throw new InvalidArgumentException('Table name must be a string.');
        }
        $map = $item['map'] ?? $name;
        if (!is_string($map)) {
            throw new InvalidArgumentException('Map name must be a string.');
        }

        $this->dictionaryMeta[$name] = [
            'table' => $table,
            'map'   => $map,
        ];
    }

    /**
     * Obtain all dictionary information.
     *
     * @return Traversable<int, DictionaryInformation>
     */
    public function getAvailableDictionaryInformation(): Traversable
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

                yield new DictionaryInformation(self::ALL_TABLES, $sourceLanguage, $targetLanguage);
            }
        }
    }

    /**
     * Fetch all languages from Contao.
     *
     * @return list<string>
     */
    private function getContaoLanguages(): array
    {
        $builder = $this->connection
            ->createQueryBuilder()
            ->select('language')
            ->from('tl_page')
            ->where('type=:type')
            ->setParameter('type', 'root')
            ->orderBy('fallback')
            ->addOrderBy('sorting');
        /** @var list<string> $languages */
        $languages = $this->connection
            ->executeQuery($builder->getSQL(), $builder->getParameters(), $builder->getParameterTypes())
            ->fetchFirstColumn();

        return $languages;
    }

    /** @psalm-assert list<TContaoDictionaryMetaDataInput|string> $dictionaryMeta */
    private function checkMeta(array $dictionaryMeta): void
    {
        /** @var mixed $item */
        foreach ($dictionaryMeta as $item) {
            if (is_string($item)) {
                continue;
            }
            if (is_array($item)) {
                if (!is_string($name = $item['name'] ?? null)) {
                    throw new InvalidArgumentException('Name must be present and a string.');
                }
                if (!is_string($item['table'] ?? $name)) {
                    throw new InvalidArgumentException('Table name must be a string.');
                }
                if (!is_string($item['map'] ?? $name)) {
                    throw new InvalidArgumentException('Map name must be a string.');
                }
                return;
            }

            throw new InvalidArgumentException('Invalid meta data');
        }
    }
}
