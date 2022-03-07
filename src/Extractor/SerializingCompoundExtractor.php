<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor;

use function is_array;
use function serialize;
use function unserialize;

/**
 * This extracts from a serialized field.
 */
class SerializingCompoundExtractor extends AbstractSerializingCompoundExtractor
{
    protected function decode(string $value): array
    {
        /** @var mixed $content */
        $content = unserialize($value, ['allowed_classes' => false]);

        if (is_array($content)) {
            /** @var array<string, mixed> $content */
            return $content;
        }
        return [];
    }

    /**
     * {@inheritDoc}
     */
    protected function encode(array $value): string
    {
        return serialize($value);
    }
}
