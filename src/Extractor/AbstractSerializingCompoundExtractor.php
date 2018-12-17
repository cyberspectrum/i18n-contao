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
 * This helps extracting from serialized fields.
 */
abstract class AbstractSerializingCompoundExtractor implements MultiStringExtractorInterface
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
        return (!array_key_exists($this->colName, $row) || \is_array($this->decode($row[$this->colName])));
    }

    /**
     * {@inheritDoc}
     */
    public function keys(array $row): \Traversable
    {
        if (!array_key_exists($this->colName, $row) || (null === $row[$this->colName])) {
            return;
        }
        $content = $this->decode($row[$this->colName]);
        foreach ($this->extractors as $key => $extractor) {
            if (array_key_exists($key, $content)) {
                switch (true) {
                    case $extractor instanceof MultiStringExtractorInterface:
                        foreach ($extractor->keys($content) as $subKey) {
                            yield $key . '.' . $subKey;
                        }
                        break;
                    case $extractor instanceof StringExtractorInterface:
                        yield $key;
                        break;
                    default:
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
        if (!array_key_exists($this->colName, $row) || (null === $row[$this->colName])) {
            return null;
        }
        $content = $this->decode($row[$this->colName]);
        $chunks  = explode('.', $path);

        if (!array_key_exists($chunks[0], $content)) {
            return null;
        }

        if (null === ($extractor = $this->extractors[$chunks[0]] ?? null)) {
            throw new \InvalidArgumentException('Sub extractor ' . $chunks[0] . ' not found');
        }

        switch (true) {
            case $extractor instanceof MultiStringExtractorInterface:
                return $extractor->get(substr($path, (\strlen($chunks[0]) + 1)), $content);
            case $extractor instanceof StringExtractorInterface:
                return $extractor->get($content);
            default:
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException When the extractor can not be found.
     */
    public function set(string $path, array &$row, string $value = null): void
    {
        if (!array_key_exists($this->colName, $row) || (null === $row[$this->colName])) {
            $row[$this->colName] = $this->encode([]);
        }
        $content = $this->decode($row[$this->colName]);
        $chunks  = explode('.', $path);

        if (null === ($extractor = $this->extractors[$chunks[0]] ?? null)) {
            throw new \InvalidArgumentException('Sub extractor ' . $chunks[0] . ' not found');
        }

        switch (true) {
            case $extractor instanceof MultiStringExtractorInterface:
                $extractor->set(substr($path, (\strlen($chunks[0]) + 1)), $content, $value);
                break;
            case $extractor instanceof StringExtractorInterface:
                if (strlen($path) > strlen($chunks[0])) {
                    throw new \InvalidArgumentException('String extractor may not contain sub extractor: "'
                        . substr($path, (strlen($chunks[0]) + 1)) . '"');
                }
                $extractor->set($content, $value);
                break;
            default:
        }

        $row[$this->colName] = $this->encode($content);
    }

    /**
     * Add an extractor.
     *
     * @param ExtractorInterface $extractor The extractor to add.
     *
     * @return self
     *
     * @throws \InvalidArgumentException When the extractor implements neither string nor subdirectory interface.
     */
    public function addExtractor(ExtractorInterface $extractor): self
    {
        switch (true) {
            case $extractor instanceof MultiStringExtractorInterface:
            case $extractor instanceof StringExtractorInterface:
                $this->extractors[$extractor->name()] = $extractor;
                return $this;
            default:
        }

        throw new \InvalidArgumentException('Unknown extractor type ' . \get_class($extractor));
    }

    /**
     * Decode a value.
     *
     * @param string $value The value to decode.
     *
     * @return mixed
     */
    abstract protected function decode(string $value): array;

    /**
     * Encode a value.
     *
     * @param array $value The value to encode.
     *
     * @return string
     */
    abstract protected function encode(array $value): string;
}
