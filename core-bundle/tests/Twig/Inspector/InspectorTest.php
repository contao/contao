<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Inspector;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Inspector\InspectionException;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\ArrayLoader;

class InspectorTest extends TestCase
{
    public function testInspectsTemplate(): void
    {
        $twig = new Environment(new ArrayLoader([
            'foo.html.twig' => '{% block foo %}{% block bar %}demo content{% endblock %}{% endblock %}',
        ]));

        $information = (new Inspector($twig))->inspectTemplate('foo.html.twig');

        $this->assertSame('foo.html.twig', $information->getName());
        $this->assertSame(['foo', 'bar'], $information->getBlocks());
        $this->assertSame('{% block foo %}{% block bar %}demo content{% endblock %}{% endblock %}', $information->getCode());
    }

    public function testCapturesErrorsWhenFailingToInspect(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->method('load')
            ->with('foo.html.twig')
            ->willThrowException($this->createMock(LoaderError::class))
        ;

        $inspector = new Inspector($twig);

        $this->expectException(InspectionException::class);
        $this->expectExceptionMessage('Could not inspect template "foo.html.twig".');

        $inspector->inspectTemplate('foo.html.twig');
    }
}
