<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao;

use CyberSpectrum\I18N\Contao\Extractor\StringExtractorInterface;
use CyberSpectrum\I18N\Contao\Extractor\MultiStringExtractorInterface;
use CyberSpectrum\I18N\TranslationValue\WritableTranslationValueInterface;
use InvalidArgumentException;

use function get_class;

/** This is the Contao translation value writer. */
class WritableTranslationValue extends TranslationValue implements WritableTranslationValueInterface
{
    public function setSource(string $value): void
    {
        $row = $this->getSourceRow();
        $this->setValue($row, $value);
        $this->dictionary->updateRow($this->sourceId, $row);
    }

    public function setTarget(string $value): void
    {
        $row = $this->getTargetRow();
        $this->setValue($row, $value);
        $this->dictionary->updateRow($this->targetId, $row);
    }

    public function clearSource(): void
    {
        $row = $this->getSourceRow();
        $this->setValue($row, null);
        $this->dictionary->updateRow($this->sourceId, $row);
    }

    public function clearTarget(): void
    {
        $row = $this->getTargetRow();
        $this->setValue($row, null);
        $this->dictionary->updateRow($this->targetId, $row);
    }

    /**
     * Set a value.
     *
     * @param array<string, mixed> $row   The row to get the value from.
     * @param string|null          $value The value to set.
     *
     * @throws InvalidArgumentException When the extractor is of unknown type.
     */
    private function setValue(array &$row, ?string $value): void
    {
        switch (true) {
            case $this->extractor instanceof MultiStringExtractorInterface:
                $this->extractor->set($this->trail, $row, $value);
                break;
            case $this->extractor instanceof StringExtractorInterface:
                $this->extractor->set($row, $value);
                break;
            default:
                throw new InvalidArgumentException('Unknown extractor type ' . get_class($this->extractor));
        }
    }
}
