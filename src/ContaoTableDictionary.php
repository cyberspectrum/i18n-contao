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

use CyberSpectrum\I18N\Contao\Extractor\ExtractorInterface;
use CyberSpectrum\I18N\Contao\Extractor\MultiStringExtractorInterface;
use CyberSpectrum\I18N\Contao\Mapping\MappingInterface;
use CyberSpectrum\I18N\Dictionary\WritableDictionaryInterface;
use CyberSpectrum\I18N\Exception\NotSupportedException;
use CyberSpectrum\I18N\TranslationValue\TranslationValueInterface;
use CyberSpectrum\I18N\TranslationValue\WritableTranslationValueInterface;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * This provides access to a Contao table.
 */
class ContaoTableDictionary implements WritableDictionaryInterface
{
    use LoggerAwareTrait;

    /**
     * The table name.
     *
     * @var string
     */
    private $tableName;

    /**
     * The source language.
     *
     * @var string
     */
    private $sourceLanguage;

    /**
     * The target language.
     *
     * @var string
     */
    private $targetLanguage;

    /**
     * Connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * The page map.
     *
     * @var MappingInterface
     */
    private $idMap;

    /**
     * The extractors.
     *
     * @var ExtractorInterface[]
     */
    private $extractors = [];

    /**
     * Create a new instance.
     *
     * @param string               $tableName      The table name.
     * @param string               $sourceLanguage The source language.
     * @param string               $targetLanguage The target language.
     * @param Connection           $connection     The database connection.
     * @param MappingInterface     $idMap          The page map.
     * @param ExtractorInterface[] $extractors     The extractors.
     *
     * @throws \InvalidArgumentException When one of the passed extractors does not implement the interface.
     */
    public function __construct(
        string $tableName,
        string $sourceLanguage,
        string $targetLanguage,
        Connection $connection,
        MappingInterface $idMap,
        array $extractors
    ) {
        $this->tableName      = $tableName;
        $this->sourceLanguage = $sourceLanguage;
        $this->targetLanguage = $targetLanguage;
        $this->connection     = $connection;
        $this->idMap          = $idMap;
        $this->setLogger(new NullLogger());
        foreach ($extractors as $extractor) {
            if (!$extractor instanceof ExtractorInterface) {
                throw new \InvalidArgumentException('Object is not an extractor ' . \get_class($extractor));
            }
            $this->extractors[$extractor->name()] = $extractor;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function keys(): \Traversable
    {
        foreach ($this->idMap->sourceIds() as $sourceId) {
            if (!$this->idMap->hasTargetFor($sourceId)) {
                continue;
            }

            foreach ($this->getKeysForSource($sourceId) as $propKey) {
                yield $sourceId . '.' . $propKey;
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws NotSupportedException When the key is in bad format or the extractor can not be found.
     */
    public function get(string $key): TranslationValueInterface
    {
        $chunks = explode('.', $key);
        if (\count($chunks) < 2) {
            throw new NotSupportedException(
                $this,
                'Key ' . $key . ' is in bad format (need: [id].[prop-name])'
            );
        }

        // [id].propname
        if (null === ($extractor = $this->getExtractor($chunks[1]))) {
            throw new NotSupportedException(
                $this,
                'Key "' . $key . '" is not supported (no extractor for "' . $chunks[1] . '" found)'
            );
        }

        $sourceId = (int) $chunks[0];
        $targetId = $this->idMap->getTargetIdFor($sourceId);

        return $this->createValueReader($sourceId, $targetId, $extractor, implode('.', \array_slice($chunks, 2)));
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        $chunks = explode('.', $key);
        if (\count($chunks) < 2) {
            return false;
        }

        // [id].propname
        if (null === $this->getExtractor($chunks[1])) {
            return false;
        }

        return $this->idMap->hasTargetFor((int) $chunks[0]);
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    /**
     * {@inheritDoc}
     */
    public function getTargetLanguage(): string
    {
        return $this->targetLanguage;
    }

    /**
     * {@inheritDoc}
     *
     * @throws NotSupportedException Adding is not supported by this class.
     */
    public function add(string $key): WritableTranslationValueInterface
    {
        throw new NotSupportedException(
            $this,
            'Can not add key to Contao database: ' . $key
        );
    }

    /**
     * {@inheritDoc}
     *
     * @throws NotSupportedException Removing is not supported by this class.
     */
    public function remove(string $key): void
    {
        throw new NotSupportedException(
            $this,
            'Can not remove key from Contao database: ' . $key
        );
    }

    /**
     * {@inheritDoc}
     *
     * @throws NotSupportedException When the key is in bad format or the extractor can not be found.
     */
    public function getWritable($key): WritableTranslationValueInterface
    {
        $chunks = explode('.', $key);
        if (\count($chunks) < 2) {
            throw new NotSupportedException($this, 'Key ' . $key . ' is in bad format (need: [id].[prop-name])');
        }

        // [id].propname
        if (null === ($extractor = $this->getExtractor($chunks[1]))) {
            throw new NotSupportedException($this, 'Key ' . $key . ' is not supported (no extractor found)');
        }

        $sourceId = (int) $chunks[0];
        $targetId = $this->idMap->getTargetIdFor($sourceId);

        return $this->createValueWriter($sourceId, $targetId, $extractor, implode('.', \array_slice($chunks, 2)));
    }

    /**
     * Retrieve connection.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Fetch a row.
     *
     * @param int $idNumber The id to fetch.
     *
     * @return array
     */
    public function getRow(int $idNumber): array
    {
        return (array) $this->getConnection()->createQueryBuilder()
            ->select('*')
            ->from($this->tableName)
            ->where('id=:id')
            ->setParameter('id', $idNumber)
            ->setMaxResults(1)
            ->execute()
            ->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a row.
     *
     * @param int   $idNumber The id to fetch.
     * @param array $values   The row values to update.
     *
     * @return void
     */
    public function updateRow(int $idNumber, array $values): void
    {
        $this->connection->update($this->tableName, $values, ['id' => $idNumber]);
    }

    /**
     * Get the keys for the source id.
     *
     * @param int $sourceId The source id.
     *
     * @return \Traversable|string[]
     *
     * @throws \InvalidArgumentException When the extractor is unknown.
     */
    protected function getKeysForSource(int $sourceId): \Traversable
    {
        $row = $this->getRow($sourceId);
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
                    throw new \InvalidArgumentException('Unknown extractor type ' . \get_class($extractor));
            }
        }
    }

    /**
     * Try to get the extractor for a property path.
     *
     * @param string $propName The property path.
     *
     * @return ExtractorInterface|null
     */
    protected function getExtractor(string $propName): ?ExtractorInterface
    {
        return ($this->extractors[$propName] ?? null);
    }

    /**
     * Create a value reader instance.
     *
     * @param int                $sourceId  The source id.
     * @param int                $targetId  The target id.
     * @param ExtractorInterface $extractor The extractor to use.
     * @param string             $trail     The trailing sub path.
     *
     * @return TranslationValueInterface
     */
    protected function createValueReader(
        int $sourceId,
        int $targetId,
        ExtractorInterface $extractor,
        string $trail
    ): TranslationValueInterface {
        return new TranslationValue($this, $sourceId, $targetId, $extractor, $trail);
    }

    /**
     * Create a value writer instance.
     *
     * @param int                $sourceId  The source id.
     * @param int                $targetId  The target id.
     * @param ExtractorInterface $extractor The extractor to use.
     * @param string             $trail     The trailing sub path.
     *
     * @return WritableTranslationValueInterface
     */
    protected function createValueWriter(
        int $sourceId,
        int $targetId,
        ExtractorInterface $extractor,
        string $trail
    ): WritableTranslationValueInterface {
        return new WritableTranslationValue($this, $sourceId, $targetId, $extractor, $trail);
    }
}
