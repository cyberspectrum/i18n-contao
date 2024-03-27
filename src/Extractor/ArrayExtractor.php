<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor;

use InvalidArgumentException;
use Traversable;

use function array_key_exists;
use function array_slice;
use function explode;
use function get_class;
use function implode;
use function is_array;

/**
 * This extracts numerical indexed arrays of values.
 */
class ArrayExtractor implements MultiStringExtractorInterface
{
    /** The column name. */
    private string $colName;

    /**
     * The extractors.
     *
     * @var array<string, ExtractorInterface>
     */
    private array $extractors = [];

    /**
     * @param string                   $colName       The column name.
     * @param list<ExtractorInterface> $subExtractors The sub extractors.
     */
    public function __construct(string $colName, array $subExtractors)
    {
        $this->colName = $colName;
        foreach ($subExtractors as $extractor) {
            $this->addExtractor($extractor);
        }
    }

    public function name(): string
    {
        return $this->colName;
    }

    public function supports(array $row): bool
    {
        return (!array_key_exists($this->colName, $row) || is_array($row[$this->colName]));
    }

    public function keys(array $row): Traversable
    {
        if (!$this->supports($row)) {
            return;
        }

        $content = $row[$this->colName];
        if (!is_array($content)) {
            throw new InvalidArgumentException('Invalid row value');
        }
        /** @var mixed $item */
        foreach ($content as $arrayKey => $item) {
            $this->ensureArray((string) $arrayKey, $content);
            /** @var array<string, mixed> $item */
            foreach ($this->extractors as $key => $extractor) {
                if (array_key_exists($key, $item)) {
                    $prefix = $arrayKey . '.' . $key;
                    switch (true) {
                        case $extractor instanceof MultiStringExtractorInterface:
                            foreach ($extractor->keys($item) as $subKey) {
                                yield $prefix . '.' . $subKey;
                            }
                            break;
                        case $extractor instanceof StringExtractorInterface:
                            yield $prefix;
                            break;
                        default:
                            throw new InvalidArgumentException('Unknown extractor type ' . get_class($extractor));
                    }
                }
            }
        }
    }

    public function get(string $path, array $row): ?string
    {
        if (!$this->supports($row)) {
            return null;
        }

        $arrayContent = $row[$this->colName];
        $this->ensureArray($this->colName, $arrayContent);
        $chunks  = explode('.', $path, 3);
        if (!array_key_exists($chunks[0], $arrayContent)) {
            return null;
        }
        /** @var mixed $content */
        $content = $arrayContent[$chunks[0]];
        $this->ensureArray($this->colName . '.' . $chunks[0], $content);

        $extractor = $this->getExtractor($chunks[1]);

        switch (true) {
            case $extractor instanceof MultiStringExtractorInterface:
                return $extractor->get($chunks[2], $content);
            case $extractor instanceof StringExtractorInterface:
                return $extractor->get($content);
            default:
        }

        throw new InvalidArgumentException('Unknown extractor type ' . get_class($extractor));
    }

    public function set(string $path, array &$row, ?string $value): void
    {
        if (!$this->supports($row)) {
            return;
        }
        if (!array_key_exists($this->colName, $row)) {
            $row[$this->colName] = [];
        }

        /** @var mixed $arrayContent */
        $arrayContent = &$row[$this->colName];
        $this->ensureArray($this->colName, $arrayContent);
        $chunks  = explode('.', $path);
        if (!array_key_exists($chunks[0], $arrayContent)) {
            $arrayContent[$chunks[0]] = [];
        }
        $content = &$arrayContent[$chunks[0]];
        $this->ensureArray($this->colName . '.' . $chunks[0], $content);

        $extractor = $this->getExtractor($chunks[1]);

        switch (true) {
            case $extractor instanceof MultiStringExtractorInterface:
                $extractor->set(implode('.', array_slice($chunks, 2)), $content, $value);
                return;
            case $extractor instanceof StringExtractorInterface:
                $extractor->set($content, $value);
                return;
            default:
        }

        throw new InvalidArgumentException('Unknown extractor type ' . get_class($extractor));
    }

    /**
     * Add an extractor.
     *
     * @param ExtractorInterface $extractor The extractor to add.
     */
    public function addExtractor(ExtractorInterface $extractor): void
    {
        $this->extractors[$extractor->name()] = $extractor;
    }

    private function getExtractor(string $name): ExtractorInterface
    {
        if (null === ($extractor = $this->extractors[$name] ?? null)) {
            throw new InvalidArgumentException('Sub extractor ' . $name . ' not found');
        }

        return $extractor;
    }

    /**
     * @param mixed $value
     * @psalm-assert array<string, mixed> $value
     */
    private function ensureArray(string $path, $value): void
    {
        if (!is_array($value)) {
            throw new InvalidArgumentException('Expected array at ' . $path);
        }
    }
}
