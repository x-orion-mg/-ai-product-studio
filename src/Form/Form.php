<?php

declare(strict_types=1);

namespace AIProductStudio\Form;

/**
 * Ordered list of fields belonging to one Feature.
 */
final class Form implements FormInterface
{
    /**
     * @param array<int, FieldInterface> $fields
     */
    public function __construct(private array $fields)
    {
    }

    public function add(FieldInterface $field): void
    {
        $this->fields[] = $field;
    }

    /**
     * {@inheritDoc}
     */
    public function fields(): array
    {
        return $this->fields;
    }

    public function field(string $name): ?FieldInterface
    {
        foreach ($this->fields as $field) {
            if ($field->name() === $name) {
                return $field;
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function sanitize(array $raw): array
    {
        $clean = [];

        foreach ($this->fields as $field) {
            $clean[$field->name()] = $field->sanitize($raw[$field->name()] ?? null);
        }

        return $clean;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(array $values): array
    {
        $errors = [];

        foreach ($this->fields as $field) {
            if (! $field->isRequired()) {
                continue;
            }

            $value = $values[$field->name()] ?? null;

            if ($value === null || $value === '' || $value === 0 || $value === []) {
                $errors[] = sprintf(
                    /* translators: %s: field label. */
                    __('Le champ « %s » est obligatoire.', 'ai-product-studio'),
                    $field->label()
                );
            }
        }

        return $errors;
    }
}
