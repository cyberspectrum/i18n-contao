<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor;

use InvalidArgumentException;
use Throwable;
use Traversable;

use function get_class;
use function is_array;
use function strlen;

/**
 * This helps extracting from serialized fields.
 */
abstract class AbstractSerializingCompoundExtractor implements MultiStringExtractorInterface
{
    /** The column name. */
    private string $colName;

    /**
     * The extractors.
     *
     * @var array<string, ExtractorInterface>
     */
    private $extractors = [];

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
        if (!array_key_exists($this->colName, $row)) {
            return true;
        }
        $value = $row[$this->colName];
        if (!is_string($value)) {
            return false;
        }
        try {
            $this->decode($value);
        } catch (Throwable $exception) {
            return false;
        }

        return true;
    }

    public function keys(array $row): Traversable
    {
        if (!array_key_exists($this->colName, $row) || (null === $row[$this->colName])) {
            return;
        }
        $value = $row[$this->colName];
        if (!is_string($value)) {
            throw new InvalidArgumentException('Invalid row value');
        }
        $content = $this->decode($value);
        foreach ($this->extractors as $key => $extractor) {
            if (array_key_exists($key, $content)) {
                switch (true) {
                    case $extractor instanceof MultiStringExtractorInterface:
                        foreach ($extractor->keys($content) as $subKey) {
                            yield $key . '.' . $subKey;
                        }
                        break;
                    case $extractor instanceof StringExtractorInterface:
                        yield $key;
                        break;
                    default:
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException When the extractor can not be found.
     */
    public function get(string $path, array $row): ?string
    {
        if (!array_key_exists($this->colName, $row) || (null === $row[$this->colName])) {
            return null;
        }
        $value = $row[$this->colName];
        if (!is_string($value)) {
            throw new InvalidArgumentException('Invalid row value');
        }
        $content = $this->decode($value);
        $chunks  = explode('.', $path);

        if (!array_key_exists($chunks[0], $content)) {
            return null;
        }

        $extractor = $this->getExtractor($chunks[0]);

        switch (true) {
            case $extractor instanceof MultiStringExtractorInterface:
                return $extractor->get(substr($path, (strlen($chunks[0]) + 1)), $content);
            case $extractor instanceof StringExtractorInterface:
                return $extractor->get($content);
            default:
        }

        throw new InvalidArgumentException('Unknown extractor type ' . get_class($extractor));
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException When the extractor can not be found.
     */
    public function set(string $path, array &$row, ?string $value): void
    {
        if (!array_key_exists($this->colName, $row) || (null === $row[$this->colName])) {
            $row[$this->colName] = $this->encode([]);
        }
        $rowValue = $row[$this->colName];
        if (!is_string($rowValue)) {
            throw new InvalidArgumentException('Invalid row value');
        }
        $content = $this->decode($rowValue);
        $chunks  = explode('.', $path, 2);

        $extractor = $this->getExtractor($chunks[0]);

        switch (true) {
            case $extractor instanceof MultiStringExtractorInterface:
                $extractor->set($chunks[1], $content, $value);
                break;
            case $extractor instanceof StringExtractorInterface:
                if (1 < count($chunks)) {
                    throw new InvalidArgumentException('String extractor may not contain sub extractor: "'
                        . $chunks[1] . '"');
                }
                $extractor->set($content, $value);
                break;
            default:
        }

        $row[$this->colName] = $this->encode($content);
    }

    /**
     * Add an extractor.
     *
     * @param ExtractorInterface $extractor The extractor to add.
     *
     * @throws InvalidArgumentException When the extractor implements neither string nor subdirectory interface.
     */
    public function addExtractor(ExtractorInterface $extractor): void
    {
        switch (true) {
            case $extractor instanceof MultiStringExtractorInterface:
            case $extractor instanceof StringExtractorInterface:
                $this->extractors[$extractor->name()] = $extractor;
                return;
            default:
        }

        throw new InvalidArgumentException('Unknown extractor type ' . get_class($extractor));
    }

    /**
     * Decode a value.
     *
     * @param string $value The value to decode.
     *
     * @return array<string, mixed>
     */
    abstract protected function decode(string $value): array;

    /**
     * Encode a value.
     *
     * @param array<string, mixed> $value The value to encode.
     *
     * @return string
     */
    abstract protected function encode(array $value): string;

    private function getExtractor(string $name): ExtractorInterface
    {
        if (null === ($extractor = $this->extractors[$name] ?? null)) {
            throw new InvalidArgumentException('Sub extractor ' . $name . ' not found');
        }

        return $extractor;
    }
}
