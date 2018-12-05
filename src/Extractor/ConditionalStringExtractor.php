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
 * This extracts string content if a condition matches.
 */
class ConditionalStringExtractor extends AbstractConditionalExtractor implements StringExtractorInterface
{
    /**
     * The delegator.
     *
     * @var StringExtractorInterface
     */
    private $delegate;

    /**
     * Create a new instance.
     *
     * @param ExtractorInterface $delegate   The delegate extractor.
     */
    public function __construct(ExtractorInterface $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * {@inheritDoc}
     */
    public function name(): string
    {
        return $this->delegate->name();
    }

    /**
     * {@inheritDoc}
     */
    public function supports(array $row): bool
    {
        return parent::supports($row) && $this->delegate->supports($row);
    }

    /**
     * {@inheritDoc}
     */
    public function get(array $row): ?string
    {
        return $this->delegate->get($row);
    }

    /**
     * {@inheritDoc}
     */
    public function set(array &$row, string $value = null): void
    {
        $this->delegate->set($row, $value);
    }
}
