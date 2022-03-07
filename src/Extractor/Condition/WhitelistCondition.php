<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor\Condition;

/**
 * This multiple conditions for the passed row and returns true on the first success.
 */
class WhitelistCondition implements ConditionInterface
{
    /**
     * The expression to evaluate.
     *
     * @var list<ConditionInterface>
     */
    private array $conditions;

    /**
     * Create a new instance.
     *
     * @param list<ConditionInterface> $conditions One or more conditions.
     */
    public function __construct(ConditionInterface ...$conditions)
    {
        $this->conditions = array_values($conditions);
    }

    /**
     * {@inheritDoc}
     */
    public function evaluate(array $row): bool
    {
        foreach ($this->conditions as $condition) {
            if ($condition->evaluate($row)) {
                return true;
            }
        }

        return false;
    }
}
