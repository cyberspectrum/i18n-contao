<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor;

use Traversable;

/**
 * This extracts content if a condition matches.
 */
class ConditionalMultiStringExtractor extends AbstractConditionalExtractor implements MultiStringExtractorInterface
{
    /** The delegator. */
    private MultiStringExtractorInterface $delegate;

    /**
     * @param MultiStringExtractorInterface $delegate The delegate extractor.
     */
    public function __construct(MultiStringExtractorInterface $delegate)
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

    public function keys(array $row): Traversable
    {
        return $this->delegate->keys($row);
    }

    public function get(string $path, array $row): ?string
    {
        return $this->delegate->get($path, $row);
    }

    public function set(string $path, array &$row, ?string $value): void
    {
        $this->delegate->set($path, $row, $value);
    }
}
