<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Doctrine\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

class SchemaProvider
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var SchemaTool|null
     */
    private $schemaTool;

    public function __construct(EntityManagerInterface $entityManager, SchemaTool $schemaTool = null)
    {
        $this->entityManager = $entityManager;
        $this->schemaTool = $schemaTool;
    }

    /**
     * Creates a schema from entity metadata.
     */
    public function createSchema(): Schema
    {
        if (null === $this->schemaTool) {
            $this->schemaTool = new SchemaTool($this->entityManager);
        }

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // This will trigger the contao.listener.doctrine_schema
        // listener that will append the DCA definitions.
        return $this->schemaTool->getSchemaFromMetadata($metadata);
    }
}
