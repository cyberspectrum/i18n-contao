<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor;

/**
 * This extracts string content if a condition matches.
 */
class ConditionalStringExtractor extends AbstractConditionalExtractor implements StringExtractorInterface
{
    /** The delegator. */
    private StringExtractorInterface $delegate;

    /**
     * @param StringExtractorInterface $delegate The delegate extractor.
     */
    public function __construct(StringExtractorInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    public function name(): string
    {
        return $this->delegate->name();
    }

    public function supports(array $row): bool
    {
        return parent::supports($row) && $this->delegate->supports($row);
    }

    public function get(array $row): ?string
    {
        return $this->delegate->get($row);
    }

    public function set(array &$row, ?string $value): void
    {
        $this->delegate->set($row, $value);
    }
}
