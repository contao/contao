<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Image\Studio;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\ResizerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class StudioTest extends TestCase
{
    public function testImplementsServiceSubscriberInterface(): void
    {
        $locator = $this->createMock(ContainerInterface::class);
        $studio = new Studio($locator, '/project/dir', 'files', ['jpg']);

        $this->assertInstanceOf(ServiceSubscriberInterface::class, $studio);
    }

    public function testSubscribedServices(): void
    {
        $services = [
            Studio::class,
            PictureFactoryInterface::class,
            ImageFactoryInterface::class,
            ResizerInterface::class,
            ContaoFramework::class,
            ContaoContext::class,
            'event_dispatcher',
        ];

        $this->assertEqualsCanonicalizing($services, Studio::getSubscribedServices());
    }
}
