<?php

declare(strict_types=1);

namespace AIProductStudio\Form;

/**
 * Renders a Form as WordPress admin HTML. Features never print markup.
 */
final class FormRenderer
{
    public function render(FormInterface $form): void
    {
        foreach ($form->fields() as $field) {
            $this->renderField($field);
        }
    }

    private function renderField(FieldInterface $field): void
    {
        $config = $field->config();
        $name   = 'fields[' . $field->name() . ']';
        $id     = 'aips-field-' . sanitize_html_class($field->name());
        $value  = (string) ($config['value'] ?? '');
        $help   = (string) ($config['help'] ?? '');

        if ($field->type() === 'hidden') {
            printf(
                '<input type="hidden" name="%s" id="%s" value="%s">',
                esc_attr($name),
                esc_attr($id),
                esc_attr($value)
            );

            return;
        }

        echo '<div class="aips-field">';
        printf(
            '<label for="%s">%s%s</label>',
            esc_attr($id),
            esc_html($field->label()),
            $field->isRequired() ? ' <span class="aips-required">*</span>' : ''
        );

        match ($field->type()) {
            'textarea' => $this->textarea($field, $name, $id, $value, $config),
            'select'   => $this->select($field, $name, $id, $value, $config),
            'number'   => $this->input($field, $name, $id, $value, $config, 'number'),
            default    => $this->input($field, $name, $id, $value, $config, 'text'),
        };

        if ($help !== '') {
            printf('<p class="description">%s</p>', esc_html($help));
        }

        echo '</div>';
    }

    /**
     * @param array<string, mixed> $config
     */
    private function input(FieldInterface $field, string $name, string $id, string $value, array $config, string $type): void
    {
        printf(
            '<input type="%s" name="%s" id="%s" value="%s" placeholder="%s"%s%s>',
            esc_attr($type),
            esc_attr($name),
            esc_attr($id),
            esc_attr($value),
            esc_attr((string) ($config['placeholder'] ?? '')),
            $field->isRequired() ? ' required' : '',
            $type === 'number' ? ' step="1"' : ''
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function textarea(FieldInterface $field, string $name, string $id, string $value, array $config): void
    {
        printf(
            '<textarea name="%s" id="%s" rows="%d" placeholder="%s"%s>%s</textarea>',
            esc_attr($name),
            esc_attr($id),
            (int) ($config['rows'] ?? 4),
            esc_attr((string) ($config['placeholder'] ?? '')),
            $field->isRequired() ? ' required' : '',
            esc_textarea($value)
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function select(FieldInterface $field, string $name, string $id, string $value, array $config): void
    {
        $options = is_array($config['options'] ?? null) ? $config['options'] : [];

        printf(
            '<select name="%s" id="%s" class="%s"%s>',
            esc_attr($name),
            esc_attr($id),
            esc_attr((string) ($config['class'] ?? '')),
            $field->isRequired() ? ' required' : ''
        );

        foreach ($options as $optValue => $label) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr((string) $optValue),
                selected((string) $optValue, $value, false),
                esc_html((string) $label)
            );
        }

        echo '</select>';
    }
}
