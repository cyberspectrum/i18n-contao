<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage;

use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Traversable;

use function array_diff;
use function array_key_exists;
use function array_keys;
use function array_values;
use function count;
use function get_class;
use function implode;
use function ucfirst;

/**
 * This maps page ids between a source language and a target language via the main language.
 *
 * @psalm-require-implements \CyberSpectrum\I18N\Contao\Mapping\MappingInterface
 */
trait MapTrait
{
    /** The connection. */
    private ContaoDatabase $database;

    /** The logger. */
    private LoggerInterface $logger;

    /** The source language. */
    private string $sourceLanguage;

    /** The target language. */
    private string $targetLanguage;

    /** The main language. */
    private ?string $mainLanguage = null;

    /**
     * The mapping from source to main language.
     *
     * $sourceId => $mainId
     *
     * @var array<int, int>
     */
    private array $sourceMap = [];

    /**
     * The mapping from main to source language.
     *
     * $mainId => $sourceId
     *
     * @var array<int, int>
     */
    private array $sourceMapInverse = [];

    /**
     * The mapping from target to main language.
     *
     * $targetId => $mainId
     *
     * @var array<int, int>
     */
    private array $targetMap = [];

    /**
     * The mapping from main to target language.
     *
     * $mainId => $targetId
     *
     * @var array<int, int>
     */
    private array $targetMapInverse = [];

    /**
     * The mapping from source to target language.
     *
     * $sourceId => $targetId
     *
     * @var array<int, int>
     */
    private array $map = [];

    /**
     * The inverse mapping.
     *
     * $targetId => $sourceId
     *
     * @var array<int, int>
     */
    private array $mapInverse = [];

    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    public function getTargetLanguage(): string
    {
        return $this->targetLanguage;
    }

    public function getMainLanguage(): string
    {
        if (null === $this->mainLanguage) {
            throw new LogicException('Main language has not been set.');
        }
        return $this->mainLanguage;
    }

    /** @return Traversable<int, int> */
    public function sourceIds(): Traversable
    {
        foreach ($this->mapInverse as $item) {
            yield $item;
        }
    }

    /** @return Traversable<int, int> */
    public function targetIds(): Traversable
    {
        foreach ($this->map as $item) {
            yield $item;
        }
    }

    public function hasTargetFor(int $sourceId): bool
    {
        return array_key_exists($sourceId, $this->map);
    }

    public function getTargetIdFor(int $sourceId): int
    {
        if (!array_key_exists($sourceId, $this->map)) {
            throw new InvalidArgumentException('Not mapped');
        }
        return $this->map[$sourceId];
    }

    public function getSourceIdFor(int $targetId): int
    {
        if (!array_key_exists($targetId, $this->mapInverse)) {
            throw new InvalidArgumentException('Not mapped');
        }
        return $this->mapInverse[$targetId];
    }

    public function getMainFromSource(int $sourceId): int
    {
        if (!array_key_exists($sourceId, $this->sourceMap)) {
            throw new InvalidArgumentException('Not mapped');
        }
        return $this->sourceMap[$sourceId];
    }

    public function getMainFromTarget(int $target): int
    {
        if (!array_key_exists($target, $this->targetMap)) {
            throw new InvalidArgumentException('Not mapped');
        }
        return $this->targetMap[$target];
    }

    /** Create the combined map from source and target. */
    public function combineSourceAndTargetMaps(): void
    {
        $this->analyzeMap();

        foreach ($this->targetMap as $targetId => $mainId) {
            if (!array_key_exists($mainId, $this->sourceMapInverse)) {
                continue;
            }
            $sourceId                    = $this->sourceMapInverse[$mainId];
            $this->map[$sourceId]        = $targetId;
            $this->mapInverse[$targetId] = $sourceId;
        }
    }

    /** Analyze the map for common mistakes. */
    public function analyzeMap(): void
    {
        $this->analyzeDuplicates('source', $this->sourceLanguage, $this->sourceMap, $this->sourceMapInverse);
        $this->analyzeDuplicates('target', $this->targetLanguage, $this->targetMap, $this->targetMapInverse);

        foreach ($this->targetMap as $targetId => $mainId) {
            if (!array_key_exists($mainId, $this->sourceMapInverse)) {
                $this->logger->warning(
                    'No source for target found. ' .
                    '{targetLanguage}: {targetId} => {mainLanguage}: {mainId} <= {sourceLanguage}: ?',
                    [
                        'sourceLanguage' => $this->sourceLanguage,
                        'mainLanguage'   => $this->mainLanguage,
                        'targetLanguage' => $this->targetLanguage,
                        'targetId'       => $targetId,
                        'mainId'         => $mainId,
                        'msg_type'       => 'no_source_for_target',
                        'class'          => get_class($this),
                    ]
                );
            }
        }
    }

    /**
     * Analyze duplicates in the mapping of a table.
     *
     * @param string          $mapName  The name of the map analyzed.
     * @param string          $language The language of the map.
     * @param array<int, int> $map      The map.
     * @param array<int, int> $inverse  The inverse map.
     */
    protected function analyzeDuplicates(string $mapName, string $language, array $map, array $inverse): void
    {
        if (count($inverse) !== count($map)) {
            $diff = array_diff(array_keys($map), array_values($inverse));
            foreach ($diff as $mapId) {
                $mainId   = $map[$mapId];
                $multiple = [];
                foreach ($map as $checkMapId => $checkMainId) {
                    if ($checkMainId === $mainId) {
                        $multiple[] = $checkMapId;
                    }
                }

                $this->logger->warning(
                    ucfirst($mapName) . ' map {mainLanguage} => {language}: multiple elements map to {mainId}: ' .
                    implode(', ', $multiple),
                    [
                        'language'       => $language,
                        'mainLanguage'   => $this->mainLanguage,
                        'mainId'         => $mainId,
                        'multiple'       => $multiple,
                        'msg_type'       => 'multiple_mapping_in_' . $mapName,
                        'class'          => get_class($this),
                    ]
                );
            }
        }
    }

    /** Obtain the database. */
    public function getDatabase(): ContaoDatabase
    {
        return $this->database;
    }
}
