<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor;

use InvalidArgumentException;
use Traversable;

/**
 * This interface describes a value extractor.
 */
interface MultiStringExtractorInterface extends ExtractorInterface
{
    /**
     * The keys this sub dictionary supports for the passed row.
     *
     * @param array<string, mixed> $row The row to test.
     *
     * @return Traversable<int, string>
     *
     * @throws InvalidArgumentException When the row is invalid.
     */
    public function keys(array $row): Traversable;

    /**
     * Obtain a value from the array.
     *
     * @param string               $path The path (excluding the name).
     * @param array<string, mixed> $row  The database row.
     *
     * @throws InvalidArgumentException When the path or row is invalid.
     */
    public function get(string $path, array $row): ?string;

    /**
     * Set a value in the passed array.
     *
     * @param string               $path  The path (excluding the name).
     * @param array<string, mixed> $row   The database row.
     * @param string|null          $value The value to set.
     *
     * @throws InvalidArgumentException When the path or row is invalid.
     */
    public function set(string $path, array &$row, ?string $value): void;
}
