<?php

declare(strict_types=1);

namespace AIProductStudio\Form\Fields;

final class TextField extends AbstractField
{
    public function type(): string
    {
        return 'text';
    }
}
