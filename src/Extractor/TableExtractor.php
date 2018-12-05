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
 * This extracts table values.
 */
class TableExtractor implements MultiStringExtractorInterface
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
     * @param string $colName
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
        return null !== $this->decode($row);
    }

    /**
     * {@inheritDoc}
     */
    public function keys(array $row): \Traversable
    {
        if (null === $content = $this->decode($row)) {
            return;
        }

        foreach (array_keys($content) as $rowIndex) {
            foreach (array_keys($content[$rowIndex]) as $col) {
                if (null === $content[$rowIndex][$col]) {
                    continue;
                }
                yield 'row' . $rowIndex . '.col' . $col;
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException When the path does not contain valid row and column chunks.
     */
    public function get(string $path, array $row): ?string
    {
        if (null === $content = $this->decode($row)) {
            return null;
        }

        $pathChunks = explode('.', $path);
        if (0 !== strpos($pathChunks[0], 'row')) {
            throw new \InvalidArgumentException('Path ' . $path . ' first part must be row, found: ' . $pathChunks[0]);
        }
        if (0 !== strpos($pathChunks[1], 'col')) {
            throw new \InvalidArgumentException('Path ' . $path . ' second part must be col, found: ' . $pathChunks[1]);
        }

        if ($value = ($content[(int) substr($pathChunks[0], 3)][(int) substr($pathChunks[1], 3)] ?? null)) {
            return $value;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidArgumentException When the path does not contain valid row and column chunks.
     */
    public function set(string $path, array &$row, string $value = null): void
    {
        if (null === $content = $this->decode($row)) {
            $content = [];
        }

        $pathChunks = explode('.', $path);
        if (0 !== strpos($pathChunks[0], 'row')) {
            throw new \InvalidArgumentException('Path ' . $path . ' first part must be row, found: ' . $pathChunks[0]);
        }
        if (0 !== strpos($pathChunks[1], 'col')) {
            throw new \InvalidArgumentException('Path ' . $path . ' second part must be col, found: ' . $pathChunks[1]);
        }

        $rowIndex = (int) substr($pathChunks[0], 3);
        if (!array_key_exists($rowIndex, $content)) {
            $content[$rowIndex] = [];
        }
        $colIndex = (int) substr($pathChunks[1], 3);

        $content[$rowIndex][$colIndex] = $value;

        $row[$this->name()] = serialize($content);
    }

    /**
     * Decode the row value.
     *
     * @param array $row The row.
     *
     * @return array|null
     */
    private function decode(array $row): ?array
    {
        if (null === ($encoded = $row[$this->name()])) {
            return null;
        }

        if (false === ($decoded  = unserialize($encoded, ['allowed_classes' => false]))) {
            return null;
        }
        if (!\is_array($decoded)) {
            return null;
        }

        return $decoded;
    }
}
