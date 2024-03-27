<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * This executes the expression language for the passed row.
 */
class ExpressionLanguageCondition implements ConditionInterface
{
    /** The expression to evaluate. */
    private string $expression;

    /** The expression language evaluator. */
    private ExpressionLanguage $expressionLanguage;

    /**
     * Create a new instance.
     *
     * @param ExpressionLanguage $expressionLanguage
     * @param string             $expression
     */
    public function __construct(ExpressionLanguage $expressionLanguage, string $expression)
    {
        $this->expression         = $expression;
        $this->expressionLanguage = $expressionLanguage;
    }

    public function evaluate(array $row): bool
    {
        return (bool) $this->expressionLanguage->evaluate($this->expression, ['row' => (object) $row]);
    }
}
