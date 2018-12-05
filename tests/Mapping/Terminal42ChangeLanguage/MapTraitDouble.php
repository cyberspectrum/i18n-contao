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

namespace CyberSpectrum\I18N\Contao\Test\Mapping\Terminal42ChangeLanguage;

use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\ContaoDatabase;
use CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage\MapTrait;
use Psr\Log\LoggerInterface;

/**
 * This doubles the trait for the test..
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
