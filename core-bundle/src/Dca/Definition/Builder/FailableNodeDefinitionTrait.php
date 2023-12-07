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

use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Utility methods for working with nodes that should only trigger
 * a deprecation instead of an exception for invalid configurations.
 *
 * @internal
 */
trait FailableNodeDefinitionTrait
{
    /**
     * @var mixed|null
     */
    private mixed $invalidFallback = null;

    private bool $invalidFallbackDefined = false;

    public function invalidFallbackValue(mixed $invalidFallback): self
    {
        $this->invalidFallbackDefined = true;
        $this->invalidFallback = $invalidFallback;

        return $this;
    }

    public function getInvalidFallbackValue(): mixed
    {
        return $this->invalidFallbackDefined ? $this->invalidFallback : ($this->defaultValue ?? null);
    }

    public function failableNormalizationCallback(\Closure $closure): \Closure
    {
        return function ($value) use ($closure) {
            try {
                $value = $closure($value);
            } catch (InvalidConfigurationException $e) {
                $root = $this->getRootNode();

                if (!$root->getAttribute(DcaTreeBuilder::FLAG_ALLOW_FAILABLE)) {
                    throw $e;
                }

                $this->triggerDeprecation($e, $this->createNode()->getPath());
                $value = $this->getInvalidFallbackValue();
            }

            return $value;
        };
    }

    private function triggerDeprecation(InvalidConfigurationException $e, string|null $path = null, mixed $fallbackValue = null): void
    {
        // Use a provided fallbackValue even if it is null
        $fallbackValue = isset(\func_get_args()[2]) ? $fallbackValue : $this->getInvalidFallbackValue();

        trigger_deprecation(
            'contao/core-bundle',
            '5.3',
            sprintf(
                'Setting an invalid DCA value has been deprecated and will no longer work in Contao 6. %s at path "%s". Falling back to value "%s" until a valid value is provided.',
                rtrim($e->getPrevious()?->getMessage() ?? $e->getMessage(), '.'),
                $path ?? $e->getPath(),
                var_export($fallbackValue, true),
            ),
        );
    }

    abstract private function getRootNode(): BaseNode;
}
