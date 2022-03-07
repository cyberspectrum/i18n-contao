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
        if (!is_array($result = json_decode($value, true))) {
            return [];
        }

        return $result;
    }

    protected function encode(array $value): string
    {
        return json_encode($value, JSON_FORCE_OBJECT);
    }
}
