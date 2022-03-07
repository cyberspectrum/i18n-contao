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

        return is_array($content) ? $content : [];
    }

    /**
     * {@inheritDoc}
     */
    protected function encode(array $value): string
    {
        return serialize($value);
    }
}
