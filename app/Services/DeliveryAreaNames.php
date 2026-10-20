<?php

namespace App\Services;

class DeliveryAreaNames
{
    public static function fromLabel(string $label): array
    {
        // Remove the displayed fee and notes, keeping hyphens inside settlement names.
        $label = preg_replace('/\s+[–—-]\s*\d[\d\s.,]*\s*(?:Ft|HUF).*$/iu', '', trim($label));
        $names = preg_split('/[,;\/]+/u', $label);
        $names = array_map(fn ($name) => trim(preg_replace('/\s+felé$/iu', '', trim($name))), $names);
        return array_values(array_unique(array_filter($names)));
    }
}
