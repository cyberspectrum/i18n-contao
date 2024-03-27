<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor;

use InvalidArgumentException;

/**
 * This interface describes a string value extractor.
 */
interface StringExtractorInterface extends ExtractorInterface
{
    /**
     * Obtain a value from the array.
     *
     * @param array<string, mixed> $row The database row.
     *
     * @throws InvalidArgumentException When the row is invalid.
     */
    public function get(array $row): ?string;

    /**
     * Set a value in the passed array.
     *
     * @param array<string, mixed> $row   The database row.
     * @param string|null          $value The value to set.
     *
     * @throws InvalidArgumentException When the row is invalid.
     */
    public function set(array &$row, ?string $value): void;
}
