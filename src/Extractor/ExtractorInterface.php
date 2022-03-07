<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor;

/**
 * This interface describes a value extractor.
 */
interface ExtractorInterface
{
    /** Name of the extractor value. */
    public function name(): string;

    /**
     * Test if the extractor supports the passed row.
     *
     * @param array<string, mixed> $row The row.
     */
    public function supports(array $row): bool;
}
