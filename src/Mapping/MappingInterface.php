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

namespace CyberSpectrum\I18N\Contao\Mapping;

/**
 * This interface describes an id mapper for Contao.
 */
interface MappingInterface
{
    /**
     * Obtain source language.
     *
     * @return string
     */
    public function getSourceLanguage(): string;

    /**
     * Obtain target language.
     *
     * @return string
     */
    public function getTargetLanguage(): string;

    /**
     * Obtain main language.
     *
     * @return string
     */
    public function getMainLanguage(): string;

    /**
     * Obtain the source ids.
     *
     * @return \Traversable|int[]
     */
    public function sourceIds(): \Traversable;

    /**
     * Obtain the source ids.
     *
     * @return \Traversable|int[]
     */
    public function targetIds(): \Traversable;

    /**
     * Check if the passed source has a mapping to the target language.
     *
     * @param int $sourceId The source id to check.
     *
     * @return bool
     */
    public function hasTargetFor(int $sourceId): bool;

    /**
     * Obtain the target id for the passed source.
     *
     * @param int $sourceId The source id.
     *
     * @return int
     */
    public function getTargetIdFor(int $sourceId): int;

    /**
     * Obtain the source id for the passed target.
     *
     * @param int $targetId The source id.
     *
     * @return int
     */
    public function getSourceIdFor(int $targetId): int;

    /**
     * Obtain the main id from a source id.
     *
     * @param int $sourceId The source id.
     *
     * @return int
     */
    public function getMainFromSource(int $sourceId): int;

    /**
     * Obtain the main id from a target id.
     *
     * @param int $target The target id.
     *
     * @return int
     */
    public function getMainFromTarget(int $target): int;
}
