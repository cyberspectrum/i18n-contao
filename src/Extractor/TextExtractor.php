<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor;

use InvalidArgumentException;

/**
 * This extracts text from a database row.
 */
class TextExtractor implements StringExtractorInterface
{
    /** The column name. */
    private string $colName;

    /**
     * @param string $colName The column name.
     */
    public function __construct(string $colName)
    {
        $this->colName = $colName;
    }

    public function name(): string
    {
        return $this->colName;
    }

    public function supports(array $row): bool
    {
        return array_key_exists($this->colName, $row);
    }

    public function get(array $row): ?string
    {
        $value = $row[$this->colName] ?? null;
        if (null !== $value && !is_string($value)) {
            throw new InvalidArgumentException('Invalid value contained for ' . $this->name());
        }

        return $value;
    }

    public function set(array &$row, ?string $value): void
    {
        $row[$this->colName] = $value;
    }
}
