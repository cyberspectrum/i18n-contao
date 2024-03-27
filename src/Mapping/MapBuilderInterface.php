<?php

declare(strict_types=1);

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
     */
    public function getMappingFor(string $tables, string $sourceLanguage, string $targetLanguage): MappingInterface;

    /**
     * Test if a mapping is supported.
     *
     * @param string $tablePath      The table path. For root tables, the table name only, for parented the full path.
     * @param string $sourceLanguage The source language.
     * @param string $targetLanguage The target language.
     */
    public function supports(string $tablePath, string $sourceLanguage, string $targetLanguage): bool;

    /**
     * Fetch the supported languages.
     *
     * @return list<string>
     */
    public function getSupportedLanguages(): array;
}
