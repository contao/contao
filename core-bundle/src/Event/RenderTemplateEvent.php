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

use Contao\CoreBundle\Twig\Event\TemplateNameTrait;

/**
 * @experimental
 */
class RenderTemplateEvent
{
    use TemplateNameTrait;

    /**
     * @var array<string, mixed>
     */
    private array $context;

    /**
     * @internal
     *
     * @param array<string, mixed> $context
     */
    public function __construct(string $name, array $context)
    {
        $this->name = $name;
        $this->context = $context;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function hasValue(string $key): bool
    {
        return \array_key_exists($key, $this->context);
    }

    /**
     * @return mixed
     */
    public function getValue(string $key)
    {
        if (!$this->hasValue($key)) {
            throw new \InvalidArgumentException("The context of '{$this->getName()}' did not contain the requested key '$key'.");
        }

        return $this->context[$key];
    }

    /**
     * @param mixed $value
     */
    public function setValue(string $key, $value): void
    {
        $this->context[$key] = $value;
    }
}
