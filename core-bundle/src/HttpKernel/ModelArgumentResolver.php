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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ModelArgumentResolver implements ArgumentValueResolverInterface
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;

    public function __construct(ContaoFramework $framework, ScopeMatcher $scopeMatcher)
    {
        $this->framework = $framework;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if (!$this->scopeMatcher->isContaoRequest($request)) {
            return false;
        }

        $this->framework->initialize();

        if (!is_a($argument->getType(), Model::class, true)) {
            return false;
        }

        if (!$argument->isNullable() && null === $this->fetchModel($request, $argument)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        yield $this->fetchModel($request, $argument);
    }

    private function fetchModel(Request $request, ArgumentMetadata $argument): ?Model
    {
        $name = $this->getArgumentName($request, $argument);

        if (null === $name) {
            return null;
        }

        /** @var Model $model */
        $model = $this->framework->getAdapter($argument->getType());

        return $model->findByPk($request->attributes->getInt($name));
    }

    /**
     * Returns the argument name from the model class.
     */
    private function getArgumentName(Request $request, ArgumentMetadata $argument): ?string
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
