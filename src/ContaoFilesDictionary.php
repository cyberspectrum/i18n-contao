<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao;

use CyberSpectrum\I18N\Contao\Extractor\ExtractorInterface;
use CyberSpectrum\I18N\Contao\Extractor\MultiStringExtractorInterface;
use CyberSpectrum\I18N\Dictionary\WritableDictionaryInterface;
use CyberSpectrum\I18N\Exception\NotSupportedException;
use CyberSpectrum\I18N\TranslationValue\TranslationValueInterface;
use CyberSpectrum\I18N\TranslationValue\WritableTranslationValueInterface;
use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;
use Traversable;

/** This provides access to a Contao table. */
final class ContaoFilesDictionary implements WritableDictionaryInterface
{
    use LoggerAwareTrait;

    public const TABLE_NAME = 'tl_files';

    /**
     * The extractors.
     *
     * @var array<string, ExtractorInterface>
     */
    private readonly array $extractors;

    /**
     * Create a new instance.
     *
     * @param string                   $sourceLanguage The source language.
     * @param string                   $targetLanguage The target language.
     * @param Connection               $connection     The database connection.
     * @param list<ExtractorInterface> $extractors     The extractors.
     *
     * @throws InvalidArgumentException When one of the passed extractors does not implement the interface.
     */
    public function __construct(
        private readonly string $sourceLanguage,
        private readonly string $targetLanguage,
        private readonly Connection $connection,
        array $extractors
    ) {
        $mappedExtractors = [];
        foreach ($extractors as $extractor) {
            if (!$extractor instanceof ExtractorInterface) {
                throw new InvalidArgumentException('Object is not an extractor ' . get_class($extractor));
            }
            $mappedExtractors[$extractor->name()] = $extractor;
        }
        $this->extractors = $mappedExtractors;
    }

    #[\Override]
    public function keys(): Traversable
    {
        foreach ($this->getSourceIds() as $sourceId) {
            foreach ($this->getKeysForSource($sourceId) as $propKey) {
                yield (string) $sourceId . '.' . $propKey;
            }
        }
    }

    #[\Override]
    public function get(string $key): TranslationValueInterface
    {
        $chunks = explode('.', $key);
        if (count($chunks) < 2) {
            throw new NotSupportedException(
                $this,
                'Key ' . $key . ' is in bad format (need: [id].[prop-name])'
            );
        }

        // Format is: [id].propname
        if (null === ($extractor = $this->getExtractor($chunks[1]))) {
            throw new NotSupportedException(
                $this,
                'Key "' . $key . '" is not supported (no extractor for "' . $chunks[1] . '" found)'
            );
        }

        $sourceId = (int) $chunks[0];

        return $this->createValueReader($sourceId, $extractor, implode('.', array_slice($chunks, 2)));
    }

    #[\Override]
    public function has(string $key): bool
    {
        $chunks = explode('.', $key);
        if (count($chunks) < 2) {
            return false;
        }

        // Format is: [id].propname
        if (null === $this->getExtractor($chunks[1])) {
            return false;
        }

        // We assume that any language exists as long as the source still exists.
        return $this->getRowForLanguage((int) $chunks[0], $this->sourceLanguage) !== [];
    }

    #[\Override]
    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    #[\Override]
    public function getTargetLanguage(): string
    {
        return $this->targetLanguage;
    }

    #[\Override]
    public function add(string $key): WritableTranslationValueInterface
    {
        throw new NotSupportedException(
            $this,
            'Can not add key to Contao database: ' . $key
        );
    }

    #[\Override]
    public function remove(string $key): void
    {
        throw new NotSupportedException(
            $this,
            'Can not remove key from Contao database: ' . $key
        );
    }

    #[\Override]
    public function getWritable(string $key): WritableTranslationValueInterface
    {
        $chunks = explode('.', $key);
        if (count($chunks) < 2) {
            throw new NotSupportedException($this, 'Key ' . $key . ' is in bad format (need: [id].[prop-name])');
        }

        // Format is: [id].propname
        if (null === ($extractor = $this->getExtractor($chunks[1]))) {
            throw new NotSupportedException($this, 'Key ' . $key . ' is not supported (no extractor found)');
        }

        $sourceId = (int) $chunks[0];

        return $this->createValueWriter($sourceId, $extractor, implode('.', array_slice($chunks, 2)));
    }

    /**
     * Fetch a row.
     *
     * @param int $idNumber The id to fetch.
     *
     * @return array<string, mixed>
     */
    public function getRowForLanguage(int $idNumber, string $language): array
    {
        $result = $this->getRow($idNumber);

        $meta = unserialize($result['meta'] ?? 'a:0:{}', ['allowed_classes' => false]);
        assert(is_array($meta));
        $languageMeta = $meta[$language] ?? null;
        assert(is_array($languageMeta) || null === $languageMeta);
        /** @var array<string, mixed>|null $languageMeta */
        return $languageMeta ?? [];
    }

    /**
     * Fetch a row.
     *
     * @param int                  $idNumber The id to fetch.
     * @param array<string, mixed> $values   The row values to update.
     *
     * @return void
     */
    public function updateRow(int $idNumber, string $language, array $values): void
    {
        $row = $this->getRow($idNumber);
        $meta = unserialize($row['meta'] ?? 'a:0:{}', ['allowed_classes' => false]) ?? [];
        assert(is_array($meta));
        $meta[$language] = $values;
        $row['meta'] = serialize($meta);
        $this->connection->update(self::TABLE_NAME, $row, ['id' => $idNumber]);
    }

    /** Retrieve connection. */
    private function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Fetch a row.
     *
     * @param int $idNumber The id to fetch.
     *
     * @return array{meta: ?string, ...<string, mixed>}
     */
    private function getRow(int $idNumber): array
    {
        $queryBuilder = $this->getConnection()->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where('id=:id')
            ->setParameter('id', $idNumber)
            ->setMaxResults(1);

        $result = $this
            ->connection
            ->executeQuery($queryBuilder->getSQL(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes())
            ->fetchAssociative();
        if (!is_array($result)) {
            throw new InvalidArgumentException('Failed to fetch row with id ' . (string) $idNumber);
        }
        assert(is_string($result['meta']) || null === $result['meta']);

        return $result;
    }

    /**
     * Get the keys for the source id.
     *
     * @param int $sourceId The source id.
     *
     * @return Traversable<int, string>
     *
     * @throws InvalidArgumentException When the extractor is unknown.
     */
    private function getKeysForSource(int $sourceId): Traversable
    {
        $row = $this->getRowForLanguage($sourceId, $this->sourceLanguage);
        foreach ($this->extractors as $extractor) {
            switch (true) {
                case $extractor instanceof MultiStringExtractorInterface:
                    foreach ($extractor->keys($row) as $key) {
                        yield $extractor->name() . '.' . $key;
                    }
                    break;
                case $extractor instanceof ExtractorInterface:
                    if ($extractor->supports($row)) {
                        yield $extractor->name();
                    }
                    break;
                default:
                    throw new InvalidArgumentException('Unknown extractor type ' . get_class($extractor));
            }
        }
    }

    /**
     * Try to get the extractor for a property path.
     *
     * @param string $propName The property path.
     */
    private function getExtractor(string $propName): ?ExtractorInterface
    {
        return ($this->extractors[$propName] ?? null);
    }

    /**
     * Create a value reader instance.
     *
     * @param int                $sourceId  The source id.
     * @param ExtractorInterface $extractor The extractor to use.
     * @param string             $trail     The trailing sub path.
     *
     * @return TranslationValueInterface
     */
    private function createValueReader(
        int $sourceId,
        ExtractorInterface $extractor,
        string $trail
    ): TranslationValueInterface {
        return new FilesTranslationValue($this, $sourceId, $extractor, $trail);
    }

    /**
     * Create a value writer instance.
     *
     * @param int                $sourceId  The source id.
     * @param ExtractorInterface $extractor The extractor to use.
     * @param string             $trail     The trailing sub path.
     *
     * @return WritableTranslationValueInterface
     */
    private function createValueWriter(
        int $sourceId,
        ExtractorInterface $extractor,
        string $trail
    ): WritableTranslationValueInterface {
        return new WritableFilesTranslationValue($this, $sourceId, $extractor, $trail);
    }

    /** @return iterable<int, int> */
    private function getSourceIds(): iterable
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('tl_files')
            ->setMaxResults(100)
            ->orderBy('path', 'ASC');
        $page = 0;
        while (true) {
            $rows = $queryBuilder->setFirstResult($page * 100)->executeQuery();
            $result = [];
            while ($row = $rows->fetchAssociative()) {
                /** @var array{id: string, type: string} $row */
                $result[] = (int) $row['id'];
            }

            foreach ($result as $idValue) {
                yield $idValue;
            }
            if (count($result) < 100) {
                break;
            }
            $page++;
        }
    }
}
