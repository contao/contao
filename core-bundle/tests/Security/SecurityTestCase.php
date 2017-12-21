<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Security;

use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class SecurityTestCase extends TestCase
{
    /**
     * Mocks a container with the given scope.
     *
     * @param string $scope
     *
     * @return ContainerBuilder
     */
    protected function mockContainerWithScope(string $scope): ContainerBuilder
    {
        $request = new Request();
        $request->attributes->set('_scope', $scope);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $container = $this->mockContainer();
        $container->set('request_stack', $requestStack);

        return $container;
    }
}
