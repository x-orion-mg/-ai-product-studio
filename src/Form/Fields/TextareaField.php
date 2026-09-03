<?php

declare(strict_types=1);

namespace AIProductStudio\Form\Fields;

final class TextareaField extends AbstractField
{
    public function type(): string
    {
        return 'textarea';
    }

    public function sanitize(mixed $value): mixed
    {
        return sanitize_textarea_field((string) $value);
    }
}
