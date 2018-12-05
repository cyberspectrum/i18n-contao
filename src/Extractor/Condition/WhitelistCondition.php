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

/**
 * This multiple conditions for the passed row and returns true on the first success.
 */
class WhitelistCondition implements ConditionInterface
{
    /**
     * The expression to evaluate.
     *
     * @var ConditionInterface[]
     */
    private $conditions;

    /**
     * Create a new instance.
     *
     * @param ConditionInterface $condition One or more conditions.
     *
     * @throws \RuntimeException When an argument does not implement the condition interface.
     */
    public function __construct($condition)
    {
        foreach (\func_get_args() as $condition) {
            if (!$condition instanceof ConditionInterface) {
                throw new \RuntimeException(
                    'Class ' . \get_class($condition) . ' does not implement ' . ConditionInterface::class
                );
            }
            $this->conditions[] = $condition;
        }
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
