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

use CyberSpectrum\I18N\Contao\Extractor\ExtractorInterface;
use CyberSpectrum\I18N\Contao\Extractor\StringExtractorInterface;
use CyberSpectrum\I18N\Contao\Extractor\MultiStringExtractorInterface;
use CyberSpectrum\I18N\Dictionary\DictionaryInterface;
use CyberSpectrum\I18N\Exception\TranslationNotFoundException;
use CyberSpectrum\I18N\TranslationValue\TranslationValueInterface;

/**
 * This is the Contao translation value reader.
 */
class TranslationValue implements TranslationValueInterface
{
    /**
     * The dictionary.
     *
     * @var ContaoTableDictionary
     */
    protected $dictionary;

    /**
     * Id of the source dataset.
     *
     * @var int
     */
    protected $sourceId;

    /**
     * Id of the target dataset.
     *
     * @var int
     */
    protected $targetId;

    /**
     * The extractor to use.
     *
     * @var ExtractorInterface
     */
    protected $extractor;

    /**
     * The trailing path.
     *
     * @var string
     */
    protected $trail;

    /**
     * Create a new instance.
     *
     * @param DictionaryInterface $dictionary The dictionary.
     * @param int                 $sourceId   The source id.
     * @param int                 $targetId   The target id.
     * @param ExtractorInterface  $extractor  The extractor to use.
     * @param string              $trail      The key trail to pass to the extractor (if sub dictionary).
     */
    public function __construct(
        DictionaryInterface $dictionary,
        int $sourceId,
        int $targetId,
        ExtractorInterface $extractor,
        string $trail
    ) {
        $this->dictionary = $dictionary;
        $this->sourceId   = $sourceId;
        $this->targetId   = $targetId;
        $this->extractor  = $extractor;
        $this->trail      = $trail;
    }

    /**
     * Obtain the translation key (this might be the same as the source value in some implementations).
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->sourceId . '.' . $this->extractor->name();
    }

    /**
     * Obtain the source language value.
     *
     * @return string
     */
    public function getSource(): ?string
    {
        return $this->getValue($this->getSourceRow());
    }

    /**
     * Obtain the target language value.
     *
     * @return string|null
     */
    public function getTarget(): ?string
    {
        return $this->getValue($this->getTargetRow());
    }

    /**
     * Check if the source value is empty.
     *
     * @return bool
     */
    public function isSourceEmpty(): bool
    {
        return empty($this->getSource());
    }

    /**
     * Check if the target value is empty.
     *
     * @return bool
     */
    public function isTargetEmpty(): bool
    {
        return empty($this->getTarget());
    }

    /**
     * Fetch the source row.
     *
     * @return array
     *
     * @throws TranslationNotFoundException When the key is not contained in the row.
     */
    protected function getSourceRow(): array
    {
        $row = $this->dictionary->getRow($this->sourceId);
        if (!$row) {
            throw new TranslationNotFoundException($this->getKey(), $this->dictionary);
        }

        return $row;
    }

    /**
     * Fetch the target row.
     *
     * @return array
     *
     * @throws TranslationNotFoundException When the key is not contained in the row.
     */
    protected function getTargetRow(): array
    {
        $row = $this->dictionary->getRow($this->targetId);
        if (!$row) {
            throw new TranslationNotFoundException($this->getKey(), $this->dictionary);
        }

        return $row;
    }

    /**
     * Get a value.
     *
     * @param array $row The row to get the value from.
     *
     * @return string|null
     *
     * @throws \InvalidArgumentException When the extractor is of unknown type.
     */
    protected function getValue(array $row): ?string
    {
        switch (true) {
            case $this->extractor instanceof MultiStringExtractorInterface:
                return $this->extractor->get($this->trail, $row);
            case $this->extractor instanceof StringExtractorInterface:
                return $this->extractor->get($row);
            default:
                throw new \InvalidArgumentException('Unknown extractor type ' . \get_class($this->extractor));
        }
    }
}
