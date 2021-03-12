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
use Contao\CoreBundle\Security\Logout\LogoutHandler;
use Contao\CoreBundle\Security\Logout\LogoutSuccessHandler;
use Contao\CoreBundle\Tests\Fixtures\Image\PictureFactoryWithoutResizeOptionsStub;
use Contao\GdImage;
use Contao\TestCase\DeprecatedClassesPhpunitExtension;
use Symfony\Bundle\SecurityBundle\Security\LegacyLogoutHandlerListener;

class DeprecatedClasses extends DeprecatedClassesPhpunitExtension
{
    protected function deprecationProvider(): array
    {
        $deprecations = [
            GdImage::class => ['%sUsing the "Contao\GdImage" class has been deprecated %s.'],
            PaletteNotFoundException::class => ['%sUsing the "Contao\CoreBundle\Exception\PaletteNotFoundException" class has been deprecated %s.'],
            PalettePositionException::class => ['%sUsing the "Contao\CoreBundle\Exception\PalettePositionException" class has been deprecated %s.'],
            PictureFactoryWithoutResizeOptionsStub::class => ['%s\PictureFactoryWithoutResizeOptionsStub::create()" method will require a new "ResizeOptions|null $options" argument in the next major version%s'],
        ];

        if (class_exists(LegacyLogoutHandlerListener::class)) {
            $deprecations[LogoutHandler::class] = ['%s class implements "Symfony\Component\Security\Http\Logout\LogoutHandlerInterface" that is deprecated %s'];

            $deprecations[LogoutSuccessHandler::class] = [
                '%sThe "Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler" class is deprecated%s',
                '%sclass extends "Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler" that is deprecated%s',
                '%sThe "Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface" interface is deprecated%s',
            ];
        }

        return $deprecations;
    }
}
