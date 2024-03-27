<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor\Condition;

/** This interface describes a condition to be met. */
interface ConditionInterface
{
    /**
     * Evaluate the condition for the passed row.
     *
     * @param array<string, mixed> $row The row.
     */
    public function evaluate(array $row): bool;
}
