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
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * This provides the Contao extractors.
 */
class ExtractorFactory
{
    use LoggerAwareTrait;

    /**
     * The service locators for the extractors.
     *
     * @var string[]
     */
    private $tableExtractors;

    /**
     * The extractor container holding the extractor services.
     *
     * @var ContainerInterface
     */
    private $extractorContainer;

    /**
     * Create a new instance.
     *
     * @param array              $tableExtractors    The table extractor list.
     * @param ContainerInterface $extractorContainer The container with the real extractors.
     */
    public function __construct(array $tableExtractors, ContainerInterface $extractorContainer)
    {
        $this->tableExtractors    = $tableExtractors;
        $this->extractorContainer = $extractorContainer;
        $this->setLogger(new NullLogger());
    }

    /**
     * Get the extractors for the passed table.
     *
     * @param string $tableName The table name.
     *
     * @return ExtractorInterface[]
     *
     * @throws \RuntimeException When an extractor is not contained within the container.
     */
    public function getExtractorsForTable(string $tableName): array
    {
        if (!isset($this->tableExtractors[$tableName])) {
            return [];
        }
        $extractors = [];
        foreach ($this->tableExtractors[$tableName] as $tableExtractor) {
            if (!$this->extractorContainer->has($tableExtractor)) {
                throw new \RuntimeException('Could not get extractor ' . $tableExtractor);
            }
            $extractors[] = $extractor = $this->extractorContainer->get($tableExtractor);
            $this->logger->debug('Adding extractor ' . $extractor->name());
        }

        return $extractors;
    }
}
