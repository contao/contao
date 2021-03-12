<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\PhpunitExtension;

use Contao\CoreBundle\DataContainer\PaletteNotFoundException;
use Contao\CoreBundle\DataContainer\PalettePositionException;
use Contao\CoreBundle\Tests\Fixtures\Database\DoctrineArrayStatement;
use Contao\CoreBundle\Translation\Translator;
use Contao\GdImage;
use Contao\TestCase\DeprecatedClassesPhpunitExtension;
use Doctrine\DBAL\Driver\Result;

final class DeprecatedClasses extends DeprecatedClassesPhpunitExtension
{
    protected function deprecationProvider(): array
    {
        $deprecations = [
            GdImage::class => ['Using the "Contao\GdImage" class has been deprecated %s.'],
            PaletteNotFoundException::class => ['Using the "Contao\CoreBundle\Exception\PaletteNotFoundException" class has been deprecated %s.'],
            PalettePositionException::class => ['Using the "Contao\CoreBundle\Exception\PalettePositionException" class has been deprecated %s.'],
            Translator::class => ['%simplements "Symfony\Component\Translation\TranslatorInterface" that is deprecated%s'],
        ];

        // Deprecated since doctrine/dbal 2.11.0
        if (interface_exists(Result::class)) {
            $deprecations[DoctrineArrayStatement::class] = ['%s extends "Doctrine\DBAL\Cache\ArrayStatement" that is deprecated.'];
        }

        return $deprecations;
    }
}
