<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Runtime;

use Contao\ManagerBundle\Console\ContaoApplication;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApplicationFactory
{
    public function __construct(private readonly string $projectDir)
    {
    }

    public function createHttpKernel(Request $request, bool $preview = false): HttpKernelInterface
    {
        if ($preview) {
            $request->attributes->set('_preview', true);
        }

        return ContaoKernel::fromRequest($this->projectDir, $request);
    }

    public function createConsoleApplication(InputInterface $input): ContaoApplication
    {
        return new ContaoApplication(ContaoKernel::fromInput($this->projectDir, $input));
    }
}
