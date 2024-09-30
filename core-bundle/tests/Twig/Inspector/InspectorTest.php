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
use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Contao\CoreBundle\Twig\Inspector\InspectionException;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class InspectorTest extends TestCase
{
    public function testInspectsTemplate(): void
    {
        $templates = [
            'foo.html.twig' => '{% block foo %}{% block bar %}[…]{% endblock %}{% endblock %}',
            'bar.html.twig' => '',
        ];

        $cacheData = [
            'path/to/foo.html.twig' => [
                'slots' => ['main', 'aside'],
                'blocks' => ['bar', 'foo'],
                'parent' => 'bar.html.twig',
                'uses' => [],
            ],
            'path/to/bar.html.twig' => [
                'slots' => ['header'],
                'blocks' => ['bar', 'foo'],
                'parent' => null,
                'uses' => [],
            ],
        ];

        $information = $this->getInspector($templates, $cacheData)->inspectTemplate('foo.html.twig');

        $this->assertSame('foo.html.twig', $information->getName());
        $this->assertSame(['bar', 'foo'], $information->getBlockNames());
        $this->assertSame('{% block foo %}{% block bar %}[…]{% endblock %}{% endblock %}', $information->getCode());
        $this->assertSame(['aside', 'header', 'main'], $information->getSlots());
    }

    public function testCapturesErrorsWhenFailingToInspect(): void
    {
        $inspector = $this->getInspector([]);

        $this->expectException(InspectionException::class);
        $this->expectExceptionMessage('Could not inspect template "foo.html.twig".');

        $inspector->inspectTemplate('foo.html.twig');
    }

    public function testThrowsErrorIfCacheWasNotBuilt(): void
    {
        $templates = [
            'foo.html.twig' => '[…]',
        ];

        $inspector = $this->getInspector($templates);

        $this->expectException(InspectionException::class);
        $this->expectExceptionMessage('Could not inspect template "foo.html.twig". No recorded information was found. Please clear the Twig template cache to make sure templates are recompiled.');

        $inspector->inspectTemplate('foo.html.twig');
    }

    public function testResolvesManagedNamespace(): void
    {
        $templates = [
            'foo.html.twig' => '',
        ];

        $cacheData = [
            'path/to/foo.html.twig' => [
                'slots' => ['main', 'aside'],
                'blocks' => [],
                'parent' => null,
                'uses' => [],
            ],
        ];

        $filesystemLoader = $this->createMock(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->expects($this->once())
            ->method('getFirst')
            ->with('@Contao/foo.html.twig')
            ->willReturn('foo.html.twig')
        ;

        $information = $this->getInspector($templates, $cacheData, $filesystemLoader)->inspectTemplate('@Contao/foo.html.twig');

        $this->assertSame('foo.html.twig', $information->getName());
    }

    /**
     * @param (ContaoFilesystemLoader&MockObject)|null $filesystemLoader
     */
    private function getInspector(array $templates, array|null $cacheData = null, $filesystemLoader = null): Inspector
    {
        $twig = new Environment(new ArrayLoader($templates));

        if (null !== $cacheData) {
            $cache = new ArrayAdapter();
            $cacheItem = $cache->getItem(Inspector::CACHE_KEY);
            $cacheItem->set($cacheData);
            $cache->save($cacheItem);
        } else {
            $cache = new NullAdapter();
        }

        $chains = [];

        foreach (array_keys($templates) as $template) {
            $chains[ContaoTwigUtil::getIdentifier($template)]['path/to/'.$template] = $template;
        }

        $filesystemLoader ??= $this->createMock(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->method('getInheritanceChains')
            ->willReturn($chains)
        ;

        return new Inspector($twig, $cache, $filesystemLoader);
    }
}
