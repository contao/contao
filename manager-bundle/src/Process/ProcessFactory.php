<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Process;

use Symfony\Component\Process\Process;

/**
 * @internal
 */
class ProcessFactory
{
    /**
     * @param array          $command The command to run and its arguments listed as separate entries
     * @param string|null    $cwd     The working directory or null to use the working dir of the current PHP process
     * @param array|null     $env     The environment variables or null to use the same environment as the current PHP process
     * @param mixed|null     $input   The input as stream resource, scalar or \Traversable, or null for no input
     * @param int|float|null $timeout The timeout in seconds or null to disable
     */
    public function create(array $command, string $cwd = null, array $env = null, $input = null, ?float $timeout = 60): Process
    {
        return new Process($command, $cwd, $env, $input, $timeout);
    }
}
