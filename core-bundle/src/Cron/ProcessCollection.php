<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cron;

use Symfony\Component\Process\Process;

class ProcessCollection
{
    /**
     * @var array<string, Process>
     */
    private array $processes = [];

    public function add(Process $process, string $name): self
    {
        $this->processes[$name] = $process;

        return $this;
    }

    /**
     * @return array<string, Process>
     */
    public function all(): array
    {
        return $this->processes;
    }

    public static function fromSingle(Process $process, string $name): self
    {
        $collection = new self();
        $collection->add($process, $name);

        return $collection;
    }
}
