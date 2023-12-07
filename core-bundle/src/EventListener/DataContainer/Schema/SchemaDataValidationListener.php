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

use Contao\CoreBundle\Dca\Schema\Schema;
use Contao\CoreBundle\Dca\Validation\ConfigurationValidator;
use Contao\CoreBundle\Event\Dca\SchemaCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * @internal
 *
 * Validate the data of a created schema against its resource configuration
 */
#[AsEventListener]
class SchemaDataValidationListener
{
    public function __construct(private readonly ConfigurationValidator $validator)
    {
    }

    public function __invoke(SchemaCreatedEvent $event): void
    {
        if (!$event->triggerValidation()) {
            return;
        }

        $schema = $event->getSchema();

        if (!$schema instanceof Schema) {
            return;
        }

        $this->validator->validateSchemaData($schema, true);
    }
}
