<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\Doctrine\Schema\EscargotSchemaProvider;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

/**
 * @internal
 */
#[AsDoctrineListener('postGenerateSchema')]
class DoctrineSchemaListener
{
    public function __construct(
        private readonly DcaSchemaProvider $dcaSchemaProvider,
        private readonly EscargotSchemaProvider $escargotSchemaProvider,
    ) {
    }

    /**
     * Adds the crawl queue and Contao DCA information to the Doctrine schema.
     */
    public function postGenerateSchema(GenerateSchemaEventArgs $event): void
    {
        $this->escargotSchemaProvider->appendToSchema($event->getSchema());
        $this->dcaSchemaProvider->appendToSchema($event->getSchema());
    }
}
