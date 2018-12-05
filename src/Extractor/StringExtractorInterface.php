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

/**
 * This interface describes a string value extractor.
 */
interface StringExtractorInterface extends ExtractorInterface
{
    /**
     * Obtain a value from the array.
     *
     * @param array $row The database row.
     *
     * @return string|null
     */
    public function get(array $row): ?string;

    /**
     * Set a value in the passed array.
     *
     * @param array       $row   The database row.
     * @param string|null $value The value to set.
     *
     * @return void
     */
    public function set(array &$row, string $value = null): void;
}
