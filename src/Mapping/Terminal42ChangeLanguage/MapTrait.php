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

use Psr\Log\LoggerInterface;

/**
 * This maps page ids between a source language and a target language via the main language.
 */
trait MapTrait
{
    /**
     * The connection.
     *
     * @var ContaoDatabase
     */
    private $database;

    /**
     * The logger.
     *
     * @var LoggerInterface
     */
    private $logger;

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
     * The main language.
     *
     * @var string
     */
    private $mainLanguage;

    /**
     * The mapping from source to main language.
     *
     * $sourceId => $mainId
     *
     * @var int[]
     */
    private $sourceMap = [];

    /**
     * The mapping from main to source language.
     *
     * $mainId => $sourceId
     *
     * @var int[]
     */
    private $sourceMapInverse = [];

    /**
     * The mapping from target to main language.
     *
     * $targetId => $mainId
     *
     * @var int[]
     */
    private $targetMap = [];

    /**
     * The mapping from main to target language.
     *
     * $mainId => $targetId
     *
     * @var int[]
     */
    private $targetMapInverse = [];

    /**
     * The mapping from source to target language.
     *
     * $sourceId => $targetId
     *
     * @var int[]
     */
    private $map = [];

    /**
     * The inverse mapping.
     *
     * $targetId => $sourceId
     *
     * @var int[]
     */
    private $mapInverse = [];

    /**
     * Obtain source language.
     *
     * @return string
     */
    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    /**
     * Obtain target language.
     *
     * @return string
     */
    public function getTargetLanguage(): string
    {
        return $this->targetLanguage;
    }

    /**
     * Obtain main language.
     *
     * @return string
     */
    public function getMainLanguage(): string
    {
        return $this->mainLanguage;
    }

    /**
     * Obtain the source ids.
     *
     * @return \Traversable|int[]
     */
    public function sourceIds(): \Traversable
    {
        yield from array_values($this->mapInverse);
    }

    /**
     * Obtain the source ids.
     *
     * @return \Traversable|int[]
     */
    public function targetIds(): \Traversable
    {
        yield from array_values($this->map);
    }

    /**
     * Check if the passed source has a mapping to the target language.
     *
     * @param int $sourceId The source id to check.
     *
     * @return bool
     */
    public function hasTargetFor(int $sourceId): bool
    {
        return array_key_exists($sourceId, $this->map);
    }

    /**
     * Obtain the target id for the passed source.
     *
     * @param int $sourceId The source id.
     *
     * @return int
     *
     * @throws \RuntimeException When the passed id is not mapped.
     */
    public function getTargetIdFor(int $sourceId): int
    {
        if (!array_key_exists($sourceId, $this->map)) {
            throw new \RuntimeException('Not mapped');
        }
        return $this->map[$sourceId];
    }

    /**
     * Obtain the source id for the passed target.
     *
     * @param int $targetId The source id.
     *
     * @return int
     *
     * @throws \RuntimeException When the passed id is not mapped.
     */
    public function getSourceIdFor(int $targetId): int
    {
        if (!array_key_exists($targetId, $this->mapInverse)) {
            throw new \RuntimeException('Not mapped');
        }
        return $this->mapInverse[$targetId];
    }

    /**
     * Obtain the main id from a source id.
     *
     * @param int $sourceId The source id.
     *
     * @return int
     *
     * @throws \RuntimeException When the passed id is not mapped.
     */
    public function getMainFromSource(int $sourceId): int
    {
        if (!array_key_exists($sourceId, $this->sourceMap)) {
            throw new \RuntimeException('Not mapped');
        }
        return $this->sourceMap[$sourceId];
    }

    /**
     * Obtain the main id from a target id.
     *
     * @param int $target The target id.
     *
     * @return int
     *
     * @throws \RuntimeException When the passed id is not mapped.
     */
    public function getMainFromTarget(int $target): int
    {
        if (!array_key_exists($target, $this->targetMap)) {
            throw new \RuntimeException('Not mapped');
        }
        return $this->targetMap[$target];
    }

    /**
     * Create the combined map from source and target.
     *
     * @return void
     */
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

    /**
     * Analyze the map for common mistakes.
     *
     * @return void
     */
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
                        'class'          => \get_class($this),
                    ]
                );
            }
        }
    }

    /**
     * Analyze duplicates in the mapping of a table.
     *
     * @param string $mapName  The name of the map analyzed.
     * @param string $language The language of the map.
     * @param array  $map      The map.
     * @param array  $inverse  The inverse map.
     *
     * @return void
     */
    protected function analyzeDuplicates(string $mapName, string $language, array $map, array $inverse): void
    {
        if (\count($inverse) !== \count($map)) {
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
                        'class'          => \get_class($this),
                    ]
                );
            }
        }
    }

    /**
     * Obtain the database.
     *
     * @return ContaoDatabase
     */
    public function getDatabase(): ContaoDatabase
    {
        return $this->database;
    }
}
