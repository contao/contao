<?php

namespace Contao\CoreBundle\Twig\Markup;

class SafeHtmlVariable implements \Stringable
{
    public function __construct(private readonly string $variable)
    {

    }

    public static function create(string $variable): SafeHtmlVariable
    {
        return new self($variable);
    }

    public function __toString(): string
    {
        return $this->variable;
    }
}
