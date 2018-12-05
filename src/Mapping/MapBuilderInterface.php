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
interface MapBuilderInterface
{
    /**
     * Get a mapping.
     *
     * @param string $tables         The table path. For root tables, the table name only, for parented the full path.
     * @param string $sourceLanguage The source language.
     * @param string $targetLanguage The target language.
     *
     * @return MappingInterface
     */
    public function getMappingFor(string $tables, string $sourceLanguage, string $targetLanguage): MappingInterface;

    /**
     * Test if a mapping is supported.
     *
     * @param string $tablePath      The table path. For root tables, the table name only, for parented the full path.
     * @param string $sourceLanguage The source language.
     * @param string $targetLanguage The target language.
     *
     * @return bool
     */
    public function supports(string $tablePath, string $sourceLanguage, string $targetLanguage): bool;

    /**
     * Fetch the supported languages.
     *
     * @return string[]
     */
    public function getSupportedLanguages(): array;
}
