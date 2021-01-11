<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Compat;

final class ScalarValueHolder implements SafeHTMLValueHolderInterface
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string
     */
    private $name;

    /**
     * @internal
     */
    public function __construct($value, string $name)
    {
        $this->value = $value;
        $this->name = $name;
    }

    public function __toString(): string
    {
        try {
            return (string) $this->value;
        } catch (\Throwable $e) {
            throw new \RuntimeException("Value '{$this->name}' could not be converted to string: {$e->getMessage()}", 0, $e);
        }
    }
}
