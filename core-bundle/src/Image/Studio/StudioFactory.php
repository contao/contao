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

    /**
     * Create a new image studio instance.
     *
     * As `contao.image.studio` is a non-shared service you can also directly
     * use the dependency injection container to get a single new instance.
     */
    public function __invoke(): Studio
    {
        return $this->container->get('contao.image.studio');
    }
}
