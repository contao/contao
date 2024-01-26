<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Runtime;

use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class ContentUrlRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly ContentUrlGenerator $urlGenerator)
    {
    }

    public function generate(object $content, array $parameters = [], bool $relative = false): string|null
    {
        try {
            return $this->urlGenerator->generate($content, $parameters, $relative ? UrlGeneratorInterface::ABSOLUTE_PATH : UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (ExceptionInterface) {
            return null;
        }
    }
}
