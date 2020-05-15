<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image\Studio;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class StudioFactory implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __invoke(): Studio
    {
        // The service is non-shared, so we'll get a new instance every time.
        return $this->container->get('contao.image.studio');
    }
}
