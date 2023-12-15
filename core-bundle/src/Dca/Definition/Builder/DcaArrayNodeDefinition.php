<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Definition\Builder;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\PrototypedArrayNode;

/**
 * Custom ArrayNodeDefinition to allow extra array keys in the configuration.
 *
 * As a transitional solution this node also triggers a deprecation
 * instead of an exception for missing required nodes, setting their
 * values to NULL.
 *
 * @method DcaNodeBuilder children()
 *
 * @internal
 */
class DcaArrayNodeDefinition extends ArrayNodeDefinition
{
    use FailableNodeDefinitionTrait;
    use RootAwareTrait;

    /**
     * @var bool
     */
    protected $ignoreExtraKeys = true;

    /**
     * @var bool
     */
    protected $removeExtraKeys = false;

    public function getNode(bool $forceRootNode = false): NodeInterface
    {
        $this
            ->beforeNormalization()
            ->always(
                $this->failableNormalizationCallback(
                    function ($value) {
                        // Handle non-array values
                        if (!\is_array($value)) {
                            $path = implode(
                                $this->pathSeparator,
                                array_filter([
                                    $this->parent instanceof NodeInterface ? $this->parent->getPath() : '',
                                    $this->parent instanceof PrototypedArrayNode ? $this->parent->getPrototype()->getName() : $this->name,
                                ]),
                            );

                            $exception = new InvalidConfigurationException(
                                sprintf('Invalid configuration for path "%s". Value must be an array', $path),
                            );
                            $exception->setPath($path);
                            $this->triggerDeprecation($exception, $path, []);

                            return [];
                        }

                        /**
                         * @var NodeDefinition $child
                         */
                        foreach ($this->children as $name => $child) {
                            // Handle required child nodes with missing values
                            if ($child->required && !isset($value[$name])) {
                                $path = implode(
                                    $this->pathSeparator,
                                    array_filter([
                                        $this->parent instanceof NodeInterface ? $this->parent->getPath() : '',
                                        $this->name,
                                        $name,
                                    ]),
                                );

                                $exception = new InvalidConfigurationException(
                                    sprintf('A value for "%s" must be configured', $name),
                                );
                                $exception->setPath($path);
                                $this->triggerDeprecation($exception, $path, null);

                                $value[$name] = null;
                            }
                        }

                        return $value;
                    },
                ),
            )
        ;

        return parent::getNode($forceRootNode);
    }
}
