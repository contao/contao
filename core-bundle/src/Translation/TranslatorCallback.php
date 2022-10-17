<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Translation;

use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatorCallback
{
    public function __construct(
        private TranslatorInterface $translator,
        private string $id,
        private array $parameters = [],
        private string|null $domain = null,
        private string|null $locale = null,
    ) {
    }

    public function __invoke(): string
    {
        return $this->translator->trans($this->id, $this->parameters, $this->domain, $this->locale);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getDomain(): string|null
    {
        return $this->domain;
    }

    public function getLocale(): string|null
    {
        return $this->locale;
    }
}
