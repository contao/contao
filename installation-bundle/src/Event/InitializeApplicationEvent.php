<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class InitializeApplicationEvent extends Event
{
    private ?string $output = null;

    /**
     * Returns the console output.
     */
    public function getOutput(): string
    {
        return $this->output;
    }

    /**
     * Sets the console output and stops event propagation.
     */
    public function setOutput(string $output): void
    {
        $this->output = $output;

        $this->stopPropagation();
    }

    /**
     * Checks if there is a console output.
     */
    public function hasOutput(): bool
    {
        return null !== $this->output;
    }
}
