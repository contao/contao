<?php

declare(strict_types=1);

namespace App\EventListener;

use Contao\ModuleNavigation;

class InvalidListener
{
    public function __construct(private readonly ModuleNavigation|ValidListener $invalid)
    {
    }

    public function __invoke(): void
    {
        // Do something
    }
}
