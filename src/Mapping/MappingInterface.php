<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Mapping;

use InvalidArgumentException;
use Traversable;

/**
 * This interface describes an id mapper for Contao.
 */
interface MappingInterface
{
    /** Obtain source language. */
    public function getSourceLanguage(): string;

    /** Obtain target language. */
    public function getTargetLanguage(): string;

    /** Obtain main language. */
    public function getMainLanguage(): string;

    /**
     * Obtain the source ids.
     *
     * @return Traversable<int, int>
     */
    public function sourceIds(): Traversable;

    /**
     * Obtain the source ids.
     *
     * @return Traversable<int, int>
     */
    public function targetIds(): Traversable;

    /**
     * Check if the passed source has a mapping to the target language.
     *
     * @param int $sourceId The source id to check.
     */
    public function hasTargetFor(int $sourceId): bool;

    /**
     * Obtain the target id for the passed source.
     *
     * @param int $sourceId The source id.
     *
     * @throws InvalidArgumentException When the passed id is not mapped.
     */
    public function getTargetIdFor(int $sourceId): int;

    /**
     * Obtain the source id for the passed target.
     *
     * @param int $targetId The source id.
     *
     * @throws InvalidArgumentException When the passed id is not mapped.
     */
    public function getSourceIdFor(int $targetId): int;

    /**
     * Obtain the main id from a source id.
     *
     * @param int $sourceId The source id.
     *
     * @return int
     *
     * @throws InvalidArgumentException WWhen the passed id is not mapped.
     */
    public function getMainFromSource(int $sourceId): int;

    /**
     * Obtain the main id from a target id.
     *
     * @param int $target The target id.
     *
     * @return int
     *
     * @throws InvalidArgumentException When the passed id is not mapped.
     */
    public function getMainFromTarget(int $target): int;
}
