<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures\Image;

use Contao\CoreBundle\Image\PictureFactoryInterface;

class PictureFactoryWithRandomArgumentStub implements PictureFactoryInterface
{
    public function setDefaultDensities($densities): void
    {
        throw new \RuntimeException('not implemented');
    }

    public function create($path, $size = null, array $options = null): void
    {
        throw new \RuntimeException('not implemented');
    }
}
