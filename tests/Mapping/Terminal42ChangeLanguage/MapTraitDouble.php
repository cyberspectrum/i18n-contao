<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Test\Mapping\Terminal42ChangeLanguage;

use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ContaoDatabase;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\MapTrait;
use Psr\Log\LoggerInterface;

/**
 * This doubles the trait for the tests.
 */
class MapTraitDouble
{
    use MapTrait;

    /**
     * Create a new instance.
     *
     * @param array           $sourceMap The source mapping to use.
     * @param array           $targetMap The target mapping to use.
     * @param ContaoDatabase  $database  The database.
     * @param LoggerInterface $logger    The logger.
     */
    public function __construct(array $sourceMap, array $targetMap, ContaoDatabase $database, LoggerInterface $logger)
    {
        $this->database       = $database;
        $this->logger         = $logger;
        $this->sourceLanguage = 'fr';
        $this->targetLanguage = 'de';
        $this->mainLanguage   = 'en';

        foreach ($sourceMap as $sourceId => $mainId) {
            $this->sourceMap[$sourceId]      = $mainId;
            $this->sourceMapInverse[$mainId] = $sourceId;
        }

        foreach ($targetMap as $targetId => $mainId) {
            $this->targetMap[$targetId]      = $mainId;
            $this->targetMapInverse[$mainId] = $targetId;
        }

        $this->combineSourceAndTargetMaps();
    }
}
