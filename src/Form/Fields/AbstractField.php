<?php

declare(strict_types=1);

namespace AIProductStudio\Form\Fields;

use AIProductStudio\Form\FieldInterface;

abstract class AbstractField implements FieldInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly string $name,
        private readonly string $label,
        private readonly bool $required = false,
        private readonly array $config = []
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * {@inheritDoc}
     */
    public function config(): array
    {
        return $this->config;
    }

    public function sanitize(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(
                static fn (mixed $item): mixed => is_scalar($item)
                    ? sanitize_text_field((string) $item)
                    : '',
                $value
            );
        }

        return sanitize_text_field((string) $value);
    }
}
