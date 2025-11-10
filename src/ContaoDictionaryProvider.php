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
 *
 * @api
 */
final class ContaoDictionaryProvider implements DictionaryProviderInterface, WritableDictionaryProviderInterface
{
    use LoggerAwareTrait;

    public const ALL_TABLES = 'contao';

    /**
     * The meta information.
     *
     * @var array<string, TContaoDictionaryMetaData>
     */
    private readonly array $dictionaryMeta;

    /**
     * Create a new instance.
     *
     * @param Connection                                $connection       The database connection.
     * @param ExtractorFactory                          $extractorFactory The extractor factory.
     * @param MapBuilderInterface                       $mapBuilder       The mapping builder.
     * @param list<TContaoDictionaryMetaDataInput>|null $dictionaryMeta   The dictionary meta information.
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly ExtractorFactory $extractorFactory,
        private readonly MapBuilderInterface $mapBuilder,
        ?array $dictionaryMeta
    ) {
        if ([] === $dictionaryMeta || null === $dictionaryMeta) {
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

        $mappedDictionaries = [];
        foreach ($this->mapDictionaryMeta($dictionaryMeta) as $name => $item) {
            $mappedDictionaries[$name] = $item;
        }
        $this->dictionaryMeta = $mappedDictionaries;
    }

    #[\Override]
    public function getAvailableDictionaries(): Traversable
    {
        yield from $this->getAvailableDictionaryInformation();
    }

    /**
     * {@inheritDoc}
     *
     * @throws DictionaryNotFoundException When the dictionary does not exist.
     */
    #[\Override]
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
            $dictionary = $this->getContaoDictionaryForMeta($metaData, $sourceLanguage, $targetLanguage);

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

    #[\Override]
    public function getAvailableWritableDictionaries(): Traversable
    {
        yield from $this->getAvailableDictionaryInformation();
    }

    /**
     * {@inheritDoc}
     *
     * @throws DictionaryNotFoundException When the dictionary does not exist.
     */
    #[\Override]
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
            return $this->getContaoDictionaryForMeta($this->dictionaryMeta[$name], $sourceLanguage, $targetLanguage);
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
    #[\Override]
    public function createDictionary(
        string $name,
        string $sourceLanguage,
        string $targetLanguage,
        array $customData = []
    ): WritableDictionaryInterface {
        throw new InvalidArgumentException('Creating new dictionaries is not supported.');
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

    /** @return iterable<string, TContaoDictionaryMetaData> */
    private function mapDictionaryMeta(array $dictionaryMeta): iterable
    {
        foreach ($dictionaryMeta as $item) {
            $this->checkMetaEntry($item);
            if (is_string($item)) {
                yield $item => [
                    'table' => $item,
                    'map'   => $item,
                ];
                continue;
            }
            $name = $item['name'];
            $table = $item['table'] ?? $name;
            /** @psalm-suppress DocblockTypeContradiction - array shape is not type safe. */
            if (!is_string($table)) {
                throw new InvalidArgumentException('Table name must be a string.');
            }
            $map = $item['map'] ?? $name;
            /** @psalm-suppress DocblockTypeContradiction - array shape is not type safe. */
            if (!is_string($map)) {
                throw new InvalidArgumentException('Map name must be a string.');
            }

            yield $name => [
                'table' => $table,
                'map'   => $map,
            ];
        }
    }

    /** @psalm-assert TContaoDictionaryMetaDataInput $entry */
    private function checkMetaEntry(mixed $entry): void
    {
        if (is_string($entry)) {
            return;
        }
        if (!is_array($entry)) {
            throw new InvalidArgumentException('Invalid meta data');
        }

        if (!is_string($name = $entry['name'] ?? null)) {
            throw new InvalidArgumentException('Name must be present and a string.');
        }
        if (!is_string($entry['table'] ?? $name)) {
            throw new InvalidArgumentException('Table name must be a string.');
        }
        if (!is_string($entry['map'] ?? $name)) {
            throw new InvalidArgumentException('Map name must be a string.');
        }
    }

    /** @param TContaoDictionaryMetaData $metaData */
    private function getContaoDictionaryForMeta(
        array $metaData,
        string $sourceLanguage,
        string $targetLanguage
    ): WritableDictionaryInterface {
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
}
