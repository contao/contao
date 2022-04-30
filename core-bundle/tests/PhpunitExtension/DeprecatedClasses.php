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
use Contao\CoreBundle\Security\Authentication\ContaoLoginAuthenticationListener;
use Contao\CoreBundle\Security\Authentication\Provider\AuthenticationProvider;
use Contao\CoreBundle\Tests\Fixtures\Image\PictureFactoryWithoutResizeOptionsStub;
use Contao\GdImage;
use Contao\TestCase\DeprecatedClassesPhpunitExtension;

final class DeprecatedClasses extends DeprecatedClassesPhpunitExtension
{
    protected function deprecationProvider(): array
    {
        return [
            GdImage::class => ['%sUsing the "Contao\GdImage" class has been deprecated %s.'],
            PaletteNotFoundException::class => ['%sUsing the "Contao\CoreBundle\Exception\PaletteNotFoundException" class has been deprecated %s.'],
            PalettePositionException::class => ['%sUsing the "Contao\CoreBundle\Exception\PalettePositionException" class has been deprecated %s.'],
            PictureFactoryWithoutResizeOptionsStub::class => ['%s\PictureFactoryWithoutResizeOptionsStub::create()" method will require a new "ResizeOptions|null $options" argument in the next major version%s'],
            ContaoLoginAuthenticationListener::class => ['%s"Symfony\Component\Security\Http\Firewall\AbstractAuthenticationListener" class is deprecated%s'],
            AuthenticationProvider::class => [
                '%s"Symfony\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider" class is deprecated%s',
                '%s"Symfony\Component\Security\Core\Authentication\Provider\UserAuthenticationProvider" class is deprecated%s',
                '%s"Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface" interface is deprecated%s',
            ],
        ];
    }
}
