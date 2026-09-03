<?php

declare(strict_types=1);

namespace AIProductStudio\Form;

/**
 * A single form control declared by a Feature. Rendering and sanitization stay
 * in the Form layer so Features never emit HTML or touch $_POST directly.
 */
interface FieldInterface
{
    public function name(): string;

    public function label(): string;

    public function type(): string;

    public function isRequired(): bool;

    /**
     * HTML attributes / extras (placeholder, rows, options, help, value).
     *
     * @return array<string, mixed>
     */
    public function config(): array;

    public function sanitize(mixed $value): mixed;
}
