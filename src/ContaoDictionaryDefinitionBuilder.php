<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao;

use CyberSpectrum\I18N\Configuration\Configuration;
use CyberSpectrum\I18N\Configuration\Definition\Definition;
use CyberSpectrum\I18N\Configuration\Definition\DictionaryDefinition;
use CyberSpectrum\I18N\Configuration\DefinitionBuilder\DefinitionBuilderInterface;
use InvalidArgumentException;

/**
 * Builds Contao dictionary definitions.
 *
 * @psalm-type TContaoDictionaryDefinitionConfigurationArray=array{
 *   name: string
 * }
 */
class ContaoDictionaryDefinitionBuilder implements DefinitionBuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function build(Configuration $configuration, array $data): Definition
    {
        $this->checkConfiguration($data);
        $name = $data['name'];
        unset($data['name']);
        $data['type'] = 'contao';

        return new DictionaryDefinition($name, $data);
    }

    /** @psalm-assert TContaoDictionaryDefinitionConfigurationArray $data */
    private function checkConfiguration(array $data): void
    {
        if (!is_string($data['name'] ?? null)) {
            throw new InvalidArgumentException('Missing key "name"');
        }
    }
}
