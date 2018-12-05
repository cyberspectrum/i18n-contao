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
 * This extracts content if a condition matches.
 */
class ConditionalMultiStringExtractor extends AbstractConditionalExtractor implements MultiStringExtractorInterface
{
    /**
     * The delegator.
     *
     * @var MultiStringExtractorInterface
     */
    private $delegate;

    /**
     * Create a new instance.
     *
     * @param MultiStringExtractorInterface $delegate The delegate extractor.
     */
    public function __construct(MultiStringExtractorInterface $delegate)
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
    public function keys(array $row): \Traversable
    {
        return $this->delegate->keys($row);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $path, array $row): ?string
    {
        return $this->delegate->get($path, $row);
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $path, array &$row, string $value = null): void
    {
        $this->delegate->set($path, $row, $value);
    }
}
