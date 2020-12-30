<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig;

class CallableProxy
{
    /**
     * @var callable
     */
    private $callable;

    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function __toString(): string
    {
        return ($this->callable)();
    }

    public function invoke(): string
    {
        return ($this->callable)(...\func_get_args());
    }

    public function getInner(): callable
    {
        return $this->callable;
    }
}
