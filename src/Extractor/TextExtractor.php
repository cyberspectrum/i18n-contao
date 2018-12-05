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
 * This extracts text from a database row.
 */
class TextExtractor implements StringExtractorInterface
{
    /**
     * The column name.
     *
     * @var string
     */
    private $colName;

    /**
     * Create a new instance.
     *
     * @param string $colName The column name.
     */
    public function __construct(string $colName)
    {
        $this->colName = $colName;
    }

    /**
     * {@inheritDoc}
     */
    public function name(): string
    {
        return $this->colName;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(array $row): bool
    {
        return array_key_exists($this->colName, $row);
    }

    /**
     * {@inheritDoc}
     */
    public function get(array $row): ?string
    {
        return $row[$this->colName];
    }

    /**
     * {@inheritDoc}
     */
    public function set(array &$row, string $value = null): void
    {
        $row[$this->colName] = $value;
    }
}
