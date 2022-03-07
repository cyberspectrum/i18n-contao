<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao;

use CyberSpectrum\I18N\Contao\Extractor\ExtractorInterface;
use CyberSpectrum\I18N\Contao\Extractor\StringExtractorInterface;
use CyberSpectrum\I18N\Contao\Extractor\MultiStringExtractorInterface;
use CyberSpectrum\I18N\Exception\TranslationNotFoundException;
use CyberSpectrum\I18N\TranslationValue\TranslationValueInterface;
use InvalidArgumentException;

use function get_class;

/** This is the Contao translation value reader. */
class TranslationValue implements TranslationValueInterface
{
    /** The dictionary. */
    protected ContaoTableDictionary $dictionary;

    /** Id of the source dataset. */
    protected int $sourceId;

    /** Id of the target dataset. */
    protected int $targetId;

    /** The extractor to use. */
    protected ExtractorInterface $extractor;

    /** The trailing path. */
    protected string $trail;

    /**
     * Create a new instance.
     *
     * @param ContaoTableDictionary $dictionary The dictionary.
     * @param int                   $sourceId   The source id.
     * @param int                   $targetId   The target id.
     * @param ExtractorInterface    $extractor  The extractor to use.
     * @param string                $trail      The key trail to pass to the extractor (if sub dictionary).
     */
    public function __construct(
        ContaoTableDictionary $dictionary,
        int $sourceId,
        int $targetId,
        ExtractorInterface $extractor,
        string $trail
    ) {
        $this->dictionary = $dictionary;
        $this->sourceId   = $sourceId;
        $this->targetId   = $targetId;
        $this->extractor  = $extractor;
        $this->trail      = $trail;
    }

    public function getKey(): string
    {
        return $this->sourceId . '.' . $this->extractor->name();
    }

    public function getSource(): ?string
    {
        return $this->getValue($this->getSourceRow());
    }

    public function getTarget(): ?string
    {
        return $this->getValue($this->getTargetRow());
    }

    public function isSourceEmpty(): bool
    {
        return empty($this->getSource());
    }

    public function isTargetEmpty(): bool
    {
        return empty($this->getTarget());
    }

    /**
     * Fetch the source row.
     *
     * @return array<string, mixed>
     *
     * @throws TranslationNotFoundException When the key is not contained in the row.
     */
    protected function getSourceRow(): array
    {
        $row = $this->dictionary->getRow($this->sourceId);
        if (!$row) {
            throw new TranslationNotFoundException($this->getKey(), $this->dictionary);
        }

        return $row;
    }

    /**
     * Fetch the target row.
     *
     * @return array<string, mixed>
     *
     * @throws TranslationNotFoundException When the key is not contained in the row.
     */
    protected function getTargetRow(): array
    {
        $row = $this->dictionary->getRow($this->targetId);
        if (!$row) {
            throw new TranslationNotFoundException($this->getKey(), $this->dictionary);
        }

        return $row;
    }

    /**
     * Get a value.
     *
     * @param array<string, mixed> $row The row to get the value from.
     *
     * @throws InvalidArgumentException When the extractor is of unknown type.
     */
    protected function getValue(array $row): ?string
    {
        switch (true) {
            case $this->extractor instanceof MultiStringExtractorInterface:
                return $this->extractor->get($this->trail, $row);
            case $this->extractor instanceof StringExtractorInterface:
                return $this->extractor->get($row);
            default:
                throw new InvalidArgumentException('Unknown extractor type ' . get_class($this->extractor));
        }
    }
}
