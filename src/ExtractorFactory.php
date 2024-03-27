<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao;

use CyberSpectrum\I18N\Contao\Extractor\ExtractorInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareTrait;
use RuntimeException;

/**
 * This provides the Contao extractors.
 */
class ExtractorFactory
{
    use LoggerAwareTrait;

    /**
     * The service locators for the extractors.
     *
     * @var array<string, list<string>>
     */
    private array $tableExtractors;

    /** The extractor container holding the extractor services. */
    private ContainerInterface $extractorContainer;

    /**
     * Create a new instance.
     *
     * @param array<string, list<string>> $tableExtractors    The table extractor list.
     * @param ContainerInterface          $extractorContainer The container with the real extractors.
     */
    public function __construct(array $tableExtractors, ContainerInterface $extractorContainer)
    {
        $this->tableExtractors    = $tableExtractors;
        $this->extractorContainer = $extractorContainer;
    }

    /**
     * Get the extractors for the passed table.
     *
     * @param string $tableName The table name.
     *
     * @return list<ExtractorInterface>
     *
     * @throws RuntimeException When an extractor is not contained within the container.
     */
    public function getExtractorsForTable(string $tableName): array
    {
        if (!isset($this->tableExtractors[$tableName])) {
            return [];
        }
        $extractors = [];
        foreach ($this->tableExtractors[$tableName] as $tableExtractor) {
            if (!$this->extractorContainer->has($tableExtractor)) {
                throw new RuntimeException('Could not get extractor ' . $tableExtractor);
            }
            $extractor = $this->extractorContainer->get($tableExtractor);
            if (!$extractor instanceof ExtractorInterface) {
                throw new RuntimeException(
                    'Expected extractor ' . $tableExtractor . ' to implement interface ' . ExtractorInterface::class
                );
            }
            $extractors[] = $extractor;
            if ($this->logger) {
                $this->logger->debug('Adding extractor ' . $extractor->name());
            }
        }

        return $extractors;
    }
}
