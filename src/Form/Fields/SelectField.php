<?php

declare(strict_types=1);

namespace AIProductStudio\Form\Fields;

final class SelectField extends AbstractField
{
    public function type(): string
    {
        return 'select';
    }

    public function sanitize(mixed $value): mixed
    {
        $value   = sanitize_text_field((string) $value);
        $options = $this->config()['options'] ?? [];
        $strict  = (bool) ($this->config()['strict'] ?? true);

        if (! $strict || ! is_array($options) || $options === []) {
            return $value;
        }

        return array_key_exists($value, $options) ? $value : (string) array_key_first($options);
    }
}
