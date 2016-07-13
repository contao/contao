<?php

/*
 * This file is part of Contao.
 *
 *  Copyright (c) 2005-2016 Leo Feyer
 *
 *  @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Doctrine\Schema;

use Doctrine\DBAL\Migrations\Provider\SchemaProviderInterface;
use Doctrine\DBAL\Schema\Schema;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class CompositeSchemaProvider implements SchemaProviderInterface
{
    /**
     * @var SchemaProviderInterface[]
     */
    private $providers;

    /**
     * Constructor.
     *
     * @param array $providers
     */
    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    /**
     * Adds a schema provider to this composite provider.
     *
     * @param SchemaProviderInterface $provider
     */
    public function add(SchemaProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * @inheritdoc
     */
    public function createSchema()
    {
        $tables = [];
        
        foreach ($this->providers as $provider) {
            try {
                $schema = $provider->createSchema();
            } catch (\UnexpectedValueException $e) {
                continue;
            }

            foreach ($schema->getTables() as $table) {
                if (array_key_exists($table->getName(), $tables)) {
                    throw new \RuntimeException(
                        sprintf('Table "%s" is defined in multiple schema providers.', $table->getName())
                    );
                }

                $tables[] = $table;
            }
        }

        return new Schema($tables);
    }
}
