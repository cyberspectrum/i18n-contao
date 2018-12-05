<?php

/**
 * This file is part of cyberspectrum/i18n-contao.
 *
 * (c) 2018 CyberSpectrum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    cyberspectrum/i18n-contao
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2018 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/i18n-contao/blob/master/LICENSE MIT
 * @filesource
 */

declare(strict_types = 1);

namespace CyberSpectrum\I18N\Contao\Extractor\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * This executes the expression language for the passed row.
 */
class ExpressionLanguageCondition implements ConditionInterface
{
    /**
     * The expression to evaluate.
     *
     * @var string
     */
    private $expression;

    /**
     * The expression language evaluator.
     *
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

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

    /**
     * {@inheritDoc}
     */
    public function evaluate(array $row): bool
    {
        return (bool) $this->expressionLanguage->evaluate($this->expression, ['row' => (object) $row]);
    }
}
