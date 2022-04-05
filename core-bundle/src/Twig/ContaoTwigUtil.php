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

use Symfony\Component\Filesystem\Path;

/**
 * @experimental
 */
final class ContaoTwigUtil
{
    /**
     * Splits a Contao name into [namespace, short name]. The short name part
     * will be null if $name is only a namespace.
     *
     * If parsing fails - i.e. if the given name does not describe a "Contao"
     * or "Contao_*" namespace - null is returned instead.
     */
    public static function parseContaoName(string $logicalNameOrNamespace): ?array
    {
        if (1 === preg_match('%^@(Contao(?:_[a-zA-Z0-9_-]+)?)(?:/(.*))?$%', $logicalNameOrNamespace, $matches)) {
            return [$matches[1], $matches[2] ?? null];
        }

        return null;
    }

    /**
     * Returns the template name without namespace and file extension.
     */
    public static function getIdentifier(string $name): string
    {
        return preg_replace('%(?:@[^/]+/)?(.*)(?:\.html5|\.html\.twig)%', '$1', $name);
    }

    /**
     * Returns true if a given template name is a legacy Contao template from a
     * "Contao" or "Contao_*" namespace and with a ".html5" file extension.
     */
    public static function isLegacyTemplate(string $logicalName): bool
    {
        if (null === $parts = self::parseContaoName($logicalName)) {
            return false;
        }

        return 'html5' === Path::getExtension($parts[1] ?? '', true);
    }
}
