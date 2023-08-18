<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\HttpKernel;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Model;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ModelArgumentResolver implements ArgumentValueResolverInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if (!$this->scopeMatcher->isContaoRequest($request)) {
            return false;
        }

        $this->framework->initialize();

        if (!is_a($argument->getType(), Model::class, true)) {
            return false;
        }

        if (!$argument->isNullable() && !$this->fetchModel($request, $argument) instanceof Model) {
            return false;
        }

        return true;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        yield $this->fetchModel($request, $argument);
    }

    private function fetchModel(Request $request, ArgumentMetadata $argument): Model|null
    {
        $name = $this->getArgumentName($request, $argument);

        if (null === $name) {
            return null;
        }

        /** @var class-string<Model> $type */
        $type = $argument->getType();
        $value = $request->attributes->get($name);

        if ($type && $value instanceof $type) {
            return $value;
        }

        // Special handling for pageModel that could be globally registered
        if (
            isset($GLOBALS['objPage'])
            && $GLOBALS['objPage'] instanceof PageModel
            && $GLOBALS['objPage']->id === (int) $value
            && is_a($type, PageModel::class, true)
        ) {
            return $GLOBALS['objPage'];
        }

        /** @var Adapter<Model> $model */
        $model = $this->framework->getAdapter($type);

        return $model->findByPk((int) $value);
    }

    /**
     * Returns the argument name from the model class.
     */
    private function getArgumentName(Request $request, ArgumentMetadata $argument): string|null
    {
        if ($request->attributes->has($argument->getName())) {
            return $argument->getName();
        }

        $className = lcfirst($this->stripNamespace($argument->getType()));

        if ($request->attributes->has($className)) {
            return $className;
        }

        return null;
    }

    /**
     * Strips the namespace from a class name.
     */
    private function stripNamespace(string $fqcn): string
    {
        if (false !== ($pos = strrpos($fqcn, '\\'))) {
            return substr($fqcn, $pos + 1);
        }

        return $fqcn;
    }
}
