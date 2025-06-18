<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace App\EventListener;

use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ValidListener
{
    public function __construct(private readonly TranslatorInterface&TranslatorBagInterface $translator)
    {
    }

    public function __invoke(): void
    {
        // Do something
    }
}
