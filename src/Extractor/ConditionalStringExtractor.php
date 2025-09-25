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

    #[\Override]
    public function name(): string
    {
        return $this->delegate->name();
    }

    #[\Override]
    public function supports(array $row): bool
    {
        return parent::supports($row) && $this->delegate->supports($row);
    }

    #[\Override]
    public function get(array $row): ?string
    {
        return $this->delegate->get($row);
    }

    #[\Override]
    public function set(array &$row, ?string $value): void
    {
        $this->delegate->set($row, $value);
    }
}
