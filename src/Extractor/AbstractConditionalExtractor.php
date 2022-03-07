<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor;

use CyberSpectrum\I18N\Contao\Extractor\Condition\ConditionInterface;

/**
 * This checks a condition.
 */
abstract class AbstractConditionalExtractor implements ExtractorInterface, ConditionalExtractorInterface
{
    /** The condition to test. */
    private ConditionInterface $condition;

    public function setCondition(ConditionInterface $condition): void
    {
        $this->condition = $condition;
    }

    public function supports(array $row): bool
    {
        return $this->condition->evaluate($row);
    }
}
