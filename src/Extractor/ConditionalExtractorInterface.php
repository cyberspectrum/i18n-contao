<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor;

use CyberSpectrum\I18N\Contao\Extractor\Condition\ConditionInterface;

/**
 * This interface describes a value extractor.
 */
interface ConditionalExtractorInterface extends ExtractorInterface
{
    /**
     * Set the passed condition
     *
     * @param ConditionInterface $condition The condition to use.
     */
    public function setCondition(ConditionInterface $condition): void;
}
