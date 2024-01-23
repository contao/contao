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
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Node\Node;

final class ContentUrlRuntime implements RuntimeExtensionInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ContentUrlGenerator $urlGenerator,
        private readonly RoutingExtension $routingExtension
    ) {
    }

    public function generate(object $content, array $parameters = [], bool $relative = false): string
    {
        try {
            return $this->urlGenerator->generate($content, $parameters, $relative ? UrlGeneratorInterface::ABSOLUTE_PATH : UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (ExceptionInterface) {
            return '';
        }
    }

    public function isUrlGenerationSafe(Node $argsNode): array
    {
        return $this->routingExtension->isUrlGenerationSafe($argsNode);
    }
}
