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
 * This extracts numerical indexed arrays of values.
 */
class ArrayExtractor implements MultiStringExtractorInterface
{
    /**
     * The column name.
     *
     * @var string
     */
    private $colName;

    /**
     * The extractors.
     *
     * @var ExtractorInterface[]
     */
    private $extractors = [];

    /**
     * Create a new instance.
     *
     * @param string $colName       The column name.
     * @param array  $subExtractors The sub extractors.
     */
    public function __construct(string $colName, array $subExtractors)
    {
        $this->colName = $colName;
        foreach ($subExtractors as $extractor) {
            $this->addExtractor($extractor);
        }
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
        return (array_key_exists($this->colName, $row) && \is_array($row[$this->colName]));
    }

    /**
     * {@inheritDoc}
     */
    public function keys(array $row): \Traversable
    {
        if (!$this->supports($row)) {
            return;
        }

        $content = $row[$this->colName];
        foreach ($content as $arrayKey => $item) {
            foreach (array_keys($this->extractors) as $key) {
                if (array_key_exists($key, $item)) {
                    yield $arrayKey . '.' . $key;
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException When the extractor can not be found.
     */
    public function get(string $path, array $row): ?string
    {
        if (!$this->supports($row)) {
            return null;
        }

        $content = $row[$this->colName];
        $chunks  = explode('.', $path);
        if (!array_key_exists($chunks[0], $content)) {
            return null;
        }
        $content = $content[$chunks[0]];

        if (null === ($extractor = $this->extractors[$chunks[1]] ?? null)) {
            throw new \InvalidArgumentException('Sub extractor ' . $chunks[1] . ' not found');
        }

        switch (true) {
            case $extractor instanceof MultiStringExtractorInterface:
                return $extractor->get(implode('.', \array_slice($chunks, 2)), $content);
            case $extractor instanceof StringExtractorInterface:
                return $extractor->get($content);
            default:
        }

        throw new \InvalidArgumentException('Unknown extractor type ' . \get_class($extractor));
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException When the extractor can not be found.
     */
    public function set(string $path, array &$row, string $value = null): void
    {
        if (!$this->supports($row)) {
            return;
        }

        $content = &$row[$this->colName];
        $chunks  = explode('.', $path);
        if (!array_key_exists($chunks[0], $content)) {
            $content[$chunks[0]] = [];
        }
        $content = &$content[$chunks[0]];

        if (null === ($extractor = $this->extractors[$chunks[1]] ?? null)) {
            throw new \InvalidArgumentException('Sub extractor ' . $chunks[1] . ' not found');
        }

        switch (true) {
            case $extractor instanceof MultiStringExtractorInterface:
                $extractor->set(implode('.', \array_slice($chunks, 2)), $content, $value);
                return;
            case $extractor instanceof StringExtractorInterface:
                $extractor->set($content, $value);
                return;
            default:
        }

        throw new \InvalidArgumentException('Unknown extractor type ' . \get_class($extractor));
    }

    /**
     * Add an extractor.
     *
     * @param ExtractorInterface $extractor The extractor to add.
     *
     * @return self
     */
    public function addExtractor(ExtractorInterface $extractor): self
    {
        $this->extractors[$extractor->name()] = $extractor;

        return $this;
    }
}
