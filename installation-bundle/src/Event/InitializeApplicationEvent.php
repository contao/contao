<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Allows to set response and output.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InitializeApplicationEvent extends Event
{
    /**
     * @var string
     */
    private $output;

    /**
     * Returns the console output.
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Sets the console output and stops event propagation.
     *
     * @param string $output
     */
    public function setOutput($output)
    {
        $this->output = $output;

        $this->stopPropagation();
    }

    /**
     * Checks if there is a console output.
     *
     * @return bool
     */
    public function hasOutput()
    {
        return null !== $this->output;
    }
}
