<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Extractor;

use function is_array;
use function json_decode;
use function json_encode;

/**
 * This extracts from a json serialized field.
 */
class JsonSerializingCompoundExtractor extends AbstractSerializingCompoundExtractor
{
    protected function decode(string $value): array
    {
        /** @var mixed $content */
        $content = json_decode($value, true);

        if (is_array($content)) {
            /** @var array<string, mixed> $content */
            return $content;
        }
        return [];
    }

    protected function encode(array $value): string
    {
        return json_encode($value, JSON_FORCE_OBJECT);
    }
}
