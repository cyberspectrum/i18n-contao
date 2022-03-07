<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor;

use InvalidArgumentException;
use Traversable;

use function array_key_exists;
use function explode;
use function is_array;
use function serialize;
use function strpos;
use function substr;
use function unserialize;

/**
 * This extracts table values.
 */
class TableExtractor implements MultiStringExtractorInterface
{
    /** The column name. */
    private string $colName;

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
        return null !== $this->decode($row);
    }

    public function keys(array $row): Traversable
    {
        if (null === $content = $this->decode($row)) {
            return;
        }

        foreach ($content as $rowIndex => $rowValues) {
            foreach ($rowValues as $col => $colValue) {
                if (null === $colValue) {
                    continue;
                }
                yield 'row' . $rowIndex . '.col' . $col;
            }
        }
    }

    public function get(string $path, array $row): ?string
    {
        if (null === $content = $this->decode($row)) {
            return null;
        }
        [$rowIndex, $colIndex] = $this->extractPath($path);

        if ($value = ($content[$rowIndex][$colIndex] ?? null)) {
            return $value;
        }

        return null;
    }

    public function set(string $path, array &$row, ?string $value): void
    {
        if (null === $content = $this->decode($row)) {
            $content = [];
        }
        [$rowIndex, $colIndex] = $this->extractPath($path);

        if (!array_key_exists($rowIndex, $content)) {
            $content[$rowIndex] = [];
        }
        $content[$rowIndex][$colIndex] = $value;

        $row[$this->name()] = serialize($content);
    }

    /**
     * Decode the row value.
     *
     * @param array<string, mixed> $row The row.
     *
     * @return array<int, array<int, ?string>>|null
     */
    private function decode(array $row): ?array
    {
        if (!is_string($encoded = $row[$this->name()])) {
            return null;
        }

        if (false === ($decoded  = unserialize($encoded, ['allowed_classes' => false]))) {
            return null;
        }
        if (!is_array($decoded)) {
            return null;
        }
        /** @var array<int, array<int, ?string>> $decoded */
        // FIXME: we might want to scan that the array signature matches but rather do not here for performance reasons.

        return $decoded;
    }

    /** @return array{0: int, 1: int} */
    private function extractPath(string $path): array
    {
        $pathChunks = explode('.', $path);
        if (2 !== count($pathChunks)) {
            throw new InvalidArgumentException('Path ' . $path . ' must be row[0-9]+.col[0-9]+, found: ' . $path);
        }
        if (0 !== strpos($pathChunks[0], 'row')) {
            throw new InvalidArgumentException('Path ' . $path . ' first part must be row, found: ' . $pathChunks[0]);
        }
        if (0 !== strpos($pathChunks[1], 'col')) {
            throw new InvalidArgumentException('Path ' . $path . ' second part must be col, found: ' . $pathChunks[1]);
        }

        if (!is_numeric($row = substr($pathChunks[0], 3))) {
            throw new \InvalidArgumentException('Non numeric row value: ' . $row);
        }
        if (!is_numeric($col = substr($pathChunks[1], 3))) {
            throw new \InvalidArgumentException('Non numeric column value: ' . $row);
        }

        return [(int) $row, (int) $col];
    }
}
