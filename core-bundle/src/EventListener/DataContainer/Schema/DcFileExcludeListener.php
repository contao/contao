<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer\Schema;

use Contao\CoreBundle\Dca\Schema\Field;
use Contao\CoreBundle\Dca\Schema\Schema;
use Contao\CoreBundle\Event\Dca\SchemaCreatingEvent;
use Contao\DC_File;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * @internal
 *
 * Assure fields in a DCA based on \Contao\DC_File are never excluded
 * since this driver does not support excluded fields
 */
#[AsEventListener(priority: 250)]
class DcFileExcludeListener
{
    public function __invoke(SchemaCreatingEvent $event): void
    {
        $data = $event->getData();

        if (Field::class !== $event->getSchema() || false === $data->get('exclude')) {
            return;
        }

        $parent = $event->getParent();

        if ($parent instanceof Schema && DC_File::class === $parent->getDca()->config()->driverClassName()) {
            $data->set('exclude', false);
        }
    }
}
