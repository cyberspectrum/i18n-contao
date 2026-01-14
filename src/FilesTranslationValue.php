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
class FilesTranslationValue implements TranslationValueInterface
{
    /**
     * Create a new instance.
     *
     * @param ContaoFilesDictionary $dictionary The dictionary.
     * @param int                   $rowId      The row id.
     * @param ExtractorInterface    $extractor  The extractor to use.
     * @param string                $trail      The key trail to pass to the extractor (if sub dictionary).
     */
    public function __construct(
        /** The dictionary. */
        protected ContaoFilesDictionary $dictionary,
        /** Id of the source dataset. */
        protected int $rowId,
        /** The extractor to use. */
        protected ExtractorInterface $extractor,
        /** The trailing path. */
        protected string $trail,
    ) {
    }

    #[\Override]
    public function getKey(): string
    {
        return (string) $this->rowId . '.' . $this->extractor->name();
    }

    #[\Override]
    public function getSource(): ?string
    {
        return $this->getValue($this->getSourceRow());
    }

    #[\Override]
    public function getTarget(): ?string
    {
        return $this->getValue($this->getTargetRow());
    }

    #[\Override]
    public function isSourceEmpty(): bool
    {
        return !(bool) $this->getSource();
    }

    #[\Override]
    public function isTargetEmpty(): bool
    {
        return !(bool) $this->getTarget();
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
        return $this->dictionary->getRowForLanguage($this->rowId, $this->dictionary->getSourceLanguage());
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
        return $this->dictionary->getRowForLanguage($this->rowId, $this->dictionary->getTargetLanguage());
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
        return match (true) {
            ($this->extractor instanceof MultiStringExtractorInterface) => $this->extractor->get($this->trail, $row),
            ($this->extractor instanceof StringExtractorInterface) => $this->extractor->get($row),
            default => throw new InvalidArgumentException('Unknown extractor type ' . get_class($this->extractor)),
        };
    }
}
