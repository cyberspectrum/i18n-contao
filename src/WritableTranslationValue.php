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

namespace CyberSpectrum\I18N\Contao;

use CyberSpectrum\I18N\Contao\Extractor\StringExtractorInterface;
use CyberSpectrum\I18N\Contao\Extractor\MultiStringExtractorInterface;
use CyberSpectrum\I18N\TranslationValue\WritableTranslationValueInterface;

/**
 * This is the Contao translation value writer.
 */
class WritableTranslationValue extends TranslationValue implements WritableTranslationValueInterface
{
    /**
     * {@inheritDoc}
     */
    public function setSource(string $value)
    {
        $row = [];
        $this->setValue($row, $value);
        $this->dictionary->updateRow($this->sourceId, $row);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTarget(string $value)
    {
        $row = [];
        $this->setValue($row, $value);
        $this->dictionary->updateRow($this->targetId, $row);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clearSource()
    {
        $row = [];
        $this->setValue($row, null);
        $this->dictionary->updateRow($this->sourceId, $row);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function clearTarget()
    {
        $row = [];
        $this->setValue($row, null);
        $this->dictionary->updateRow($this->targetId, $row);

        return $this;
    }

    /**
     * Set a value.
     *
     * @param array       $row   The row to get the value from.
     * @param string|null $value The value to set.
     *
     * @return string|null
     *
     * @throws \InvalidArgumentException When the extractor is of unknown type.
     */
    protected function setValue(array &$row, string $value = null): ?string
    {
        switch (true) {
            case $this->extractor instanceof MultiStringExtractorInterface:
                return $this->extractor->set($this->trail, $row, $value);
            case $this->extractor instanceof StringExtractorInterface:
                return $this->extractor->set($row, $value);
            default:
                throw new \InvalidArgumentException('Unknown extractor type ' . \get_class($this->extractor));
        }
    }
}
