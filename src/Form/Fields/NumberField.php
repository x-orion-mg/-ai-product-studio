<?php

declare(strict_types=1);

namespace AIProductStudio\Form\Fields;

final class NumberField extends AbstractField
{
    public function type(): string
    {
        return 'number';
    }

    public function sanitize(mixed $value): mixed
    {
        if (is_numeric($value)) {
            return str_contains((string) $value, '.') ? (float) $value : (int) $value;
        }

        return 0;
    }
}
