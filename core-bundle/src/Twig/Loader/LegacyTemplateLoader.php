<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Loader;

use Contao\CoreBundle\Framework\ContaoFramework;
use Twig\Loader\LoaderInterface;
use Twig\Source;

class LegacyTemplateLoader implements LoaderInterface
{
    // todo: This loader will make the PHP templates available as Twig templates.

    public function __construct(ContaoFramework $framework, string $projectDir)
    {
    }

    public function getSourceContext($name): Source
    {
        throw new \RuntimeException('not implemented');
    }

    public function getCacheKey($name): string
    {
        throw new \RuntimeException('not implemented');
    }

    public function isFresh($name, $time): bool
    {
        return true;
    }

    public function exists($name): bool
    {
        return false;
    }
}
