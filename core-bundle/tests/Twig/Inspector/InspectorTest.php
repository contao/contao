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
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\ArrayLoader;

class InspectorTest extends TestCase
{
    public function testInspectsTemplate(): void
    {
        $twig = new Environment(new ArrayLoader([
            'foo.html.twig' => '{% block foo %}{% block bar %}[…]{% endblock %}{% endblock %}',
        ]));

        $cache = new ArrayAdapter();
        $cacheItem = $cache->getItem(Inspector::CACHE_KEY);

        $cacheItem->set([
            'foo.html.twig' => [
                'slots' => ['main', 'aside'],
                'parent' => 'bar.html.twig',
            ],
            'bar.html.twig' => [
                'slots' => ['header'],
                'parent' => null,
            ],
        ]);

        $cache->save($cacheItem);

        $information = (new Inspector($twig, $cache))->inspectTemplate('foo.html.twig');

        $this->assertSame('foo.html.twig', $information->getName());
        $this->assertSame(['bar', 'foo'], $information->getBlocks());
        $this->assertSame('{% block foo %}{% block bar %}[…]{% endblock %}{% endblock %}', $information->getCode());
        $this->assertSame(['aside', 'header', 'main'], $information->getSlots());
    }

    public function testCapturesErrorsWhenFailingToInspect(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->method('load')
            ->with('foo.html.twig')
            ->willThrowException($this->createMock(LoaderError::class))
        ;

        $inspector = new Inspector($twig, new NullAdapter());

        $this->expectException(InspectionException::class);
        $this->expectExceptionMessage('Could not inspect template "foo.html.twig".');

        $inspector->inspectTemplate('foo.html.twig');
    }

    public function testThrowsErrorIfCacheWasNotWarmed(): void
    {
        $twig = new Environment(new ArrayLoader([
            'foo.html.twig' => '[…]',
        ]));

        $inspector = new Inspector($twig, new NullAdapter());

        $this->expectException(InspectionException::class);
        $this->expectExceptionMessage('Could not inspect template "foo.html.twig". No recorded information was found. Please clear the Twig template cache to make sure templates are recompiled.');

        $inspector->inspectTemplate('foo.html.twig');
    }
}
