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
     *
     * @return void
     */
    public function setCondition(ConditionInterface $condition): void;
}
