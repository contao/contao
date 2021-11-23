<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class GenerateSymlinksEvent extends Event
{
    private array $symlinks = [];

    public function getSymlinks(): array
    {
        return $this->symlinks;
    }

    public function addSymlink(string $target, string $link): void
    {
        $this->symlinks[$target] = $link;
    }
}
