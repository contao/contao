<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Validation;

use Contao\CoreBundle\Dca\DcaConfiguration;
use Contao\CoreBundle\Dca\Schema\ParentAwareSchemaInterface;
use Contao\CoreBundle\Dca\Schema\SchemaInterface;
use Contao\CoreBundle\Dca\Util\Path;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\PrototypedArrayNode;

class ConfigurationValidator
{
    private readonly Processor $processor;

    public function __construct()
    {
        $this->processor = new Processor();
    }

    /**
     * Validate the data of a schema using its root resource configuration
     * but only processing the subset at the schema's path in the configuration tree.
     *
     * @throws InvalidConfigurationException
     */
    public function validateSchemaData(SchemaInterface&ParentAwareSchemaInterface $schema, bool $allowFailingNodes = false): array
    {
        return $this->processor->process(
            $this->getConfigurationTreeSubset(
                (new DcaConfiguration($schema->getRoot()->getName()))
                    ->allowFailingNodes($allowFailingNodes)
                    ->getConfigTreeBuilder()
                    ->buildTree(),
                $schema->getPath(),
            ),
            [$schema->getData()->all()],
        );
    }

    private function getConfigurationTreeSubset(NodeInterface $tree, Path $path): NodeInterface
    {
        if ($path->isEmpty()) {
            return $tree;
        }

        if (!$tree instanceof ArrayNode) {
            throw new \RuntimeException('Cannot get children of node '.$tree->getPath());
        }

        $step = $path->shift();

        if ($tree instanceof PrototypedArrayNode) {
            $prototype = $tree->getPrototype();
            $prototype->setName($step);

            return $this->getConfigurationTreeSubset($prototype, $path);
        }

        $subset = $tree->getChildren()[$step] ?? null;

        if (null === $subset) {
            throw new \RuntimeException('Cannot find node '.$tree->getPath().'.'.$step);
        }

        return $this->getConfigurationTreeSubset($subset, $path);
    }
}
