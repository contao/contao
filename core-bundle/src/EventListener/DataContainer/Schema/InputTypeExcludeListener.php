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
use Contao\CoreBundle\Event\Dca\SchemaCreatingEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * @internal
 *
 * Assure fields with an input type or an input field callback
 * are excluded by default
 */
#[AsEventListener]
class InputTypeExcludeListener
{
    public function __invoke(SchemaCreatingEvent $event): void
    {
        $data = $event->getData();

        if (!is_a($event->getSchema(), Field::class, true) || null !== $data->get('exclude')) {
            return;
        }

        if ($data->get('inputType') || $data->get('input_field_callback')) {
            $data->set('exclude', true);
        }
    }
}
