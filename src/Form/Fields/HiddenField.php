<?php

declare(strict_types=1);

namespace AIProductStudio\Form\Fields;

final class HiddenField extends AbstractField
{
    public function type(): string
    {
        return 'hidden';
    }
}
