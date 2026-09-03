<?php

declare(strict_types=1);

namespace AIProductStudio\Form;

interface FormInterface
{
    /**
     * @return array<int, FieldInterface>
     */
    public function fields(): array;

    public function field(string $name): ?FieldInterface;

    /**
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    public function sanitize(array $raw): array;

    /**
     * @param array<string, mixed> $values
     *
     * @return array<int, string>
     */
    public function validate(array $values): array;
}
