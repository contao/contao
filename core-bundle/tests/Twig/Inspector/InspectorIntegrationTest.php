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

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\BlockType;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Twig\Environment;
use Twig\Source;

class InspectorIntegrationTest extends TestCase
{
    public function testAnalyzeBlockHierarchy(): void
    {
        $templates = [
            'leaf.twig' => <<<'SOURCE'
                {% extends "branch.twig" %}
                {% use "component.twig" with foo as other %}

                {# Overwriting implicit parent block: #}
                {% block baz %}
                  +
                {% endblock %}

                SOURCE,

            'branch.twig' => <<<'SOURCE'
                {% extends "root.twig" %}

                {# Enhancing parent block: #}
                {% block foo %}
                  {{ parent() }}
                  +
                {% endblock %}

                {# Overwriting parent block: #}
                {% block bar %}
                  +
                {% endblock %}
                SOURCE,

            'root.twig' => <<<'SOURCE'
                Prototype block:
                {% block foo %}{% endblock %}

                Regular blocks:
                {% block bar %}bar{% endblock %}
                {% block baz %}baz{% endblock %}
                SOURCE,

            'component.twig' => <<<'SOURCE'
                {% block component %}
                    {% block foo %}{% endblock %}
                    {% block boo %}{% endblock %}
                {% endblock %}
                SOURCE,
        ];

        $inspector = $this->getInspector($templates);

        // Test getting hierarchy of block "foo"
        $fooHierarchy = $inspector->getBlockHierarchy('leaf.twig', 'foo');
        $this->assertCount(3, $fooHierarchy);

        $this->assertSame('leaf.twig', $fooHierarchy[0]->getTemplateName());
        $this->assertSame(BlockType::transparent, $fooHierarchy[0]->getType());
        $this->assertSame('foo', $fooHierarchy[0]->getBlockName());
        $this->assertFalse($fooHierarchy[0]->isPrototype());

        $this->assertSame('branch.twig', $fooHierarchy[1]->getTemplateName());
        $this->assertSame(BlockType::enhance, $fooHierarchy[1]->getType());
        $this->assertSame('foo', $fooHierarchy[1]->getBlockName());
        $this->assertFalse($fooHierarchy[1]->isPrototype());

        $this->assertSame('root.twig', $fooHierarchy[2]->getTemplateName());
        $this->assertSame(BlockType::origin, $fooHierarchy[2]->getType());
        $this->assertSame('foo', $fooHierarchy[2]->getBlockName());
        $this->assertTrue($fooHierarchy[2]->isPrototype());

        // Test getting hierarchy of block "bar"
        $barHierarchy = $inspector->getBlockHierarchy('leaf.twig', 'bar');
        $this->assertCount(3, $barHierarchy);

        $this->assertSame('leaf.twig', $barHierarchy[0]->getTemplateName());
        $this->assertSame(BlockType::transparent, $barHierarchy[0]->getType());
        $this->assertSame('bar', $barHierarchy[0]->getBlockName());
        $this->assertFalse($barHierarchy[0]->isPrototype());

        $this->assertSame('branch.twig', $barHierarchy[1]->getTemplateName());
        $this->assertSame(BlockType::overwrite, $barHierarchy[1]->getType());
        $this->assertSame('bar', $barHierarchy[1]->getBlockName());
        $this->assertFalse($barHierarchy[1]->isPrototype());

        $this->assertSame('root.twig', $barHierarchy[2]->getTemplateName());
        $this->assertSame(BlockType::origin, $barHierarchy[2]->getType());
        $this->assertSame('bar', $barHierarchy[2]->getBlockName());
        $this->assertFalse($barHierarchy[2]->isPrototype());

        // Test getting hierarchy of block "baz"
        $bazHierarchy = $inspector->getBlockHierarchy('leaf.twig', 'baz');
        $this->assertCount(3, $bazHierarchy);

        $this->assertSame('leaf.twig', $bazHierarchy[0]->getTemplateName());
        $this->assertSame(BlockType::overwrite, $bazHierarchy[0]->getType());
        $this->assertSame('baz', $bazHierarchy[0]->getBlockName());
        $this->assertFalse($bazHierarchy[0]->isPrototype());

        $this->assertSame('branch.twig', $bazHierarchy[1]->getTemplateName());
        $this->assertSame(BlockType::transparent, $bazHierarchy[1]->getType());
        $this->assertSame('baz', $bazHierarchy[1]->getBlockName());
        $this->assertFalse($bazHierarchy[1]->isPrototype());

        $this->assertSame('root.twig', $bazHierarchy[2]->getTemplateName());
        $this->assertSame(BlockType::origin, $bazHierarchy[2]->getType());
        $this->assertSame('baz', $bazHierarchy[2]->getBlockName());
        $this->assertFalse($bazHierarchy[2]->isPrototype());

        // Test getting hierarchy of block "foo" imported as "other"
        $otherHierarchy = $inspector->getBlockHierarchy('leaf.twig', 'other');
        $this->assertCount(2, $otherHierarchy);

        $this->assertSame('leaf.twig', $otherHierarchy[0]->getTemplateName());
        $this->assertSame(BlockType::transparent, $otherHierarchy[0]->getType());
        $this->assertSame('other', $otherHierarchy[0]->getBlockName());
        $this->assertFalse($otherHierarchy[0]->isPrototype());

        $this->assertSame('component.twig', $otherHierarchy[1]->getTemplateName());
        $this->assertSame(BlockType::origin, $otherHierarchy[1]->getType());
        $this->assertSame('foo', $otherHierarchy[1]->getBlockName());
        $this->assertTrue($otherHierarchy[1]->isPrototype());
    }

    private function getInspector(array $templates): Inspector
    {
        $filesystemLoader = $this->createMock(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->method('exists')
            ->willReturnCallback(
                static fn (string $name): bool => \in_array($name, $templates, true),
            )
        ;

        $filesystemLoader
            ->method('isFresh')
            ->willReturn(true)
        ;

        $filesystemLoader
            ->method('getSourceContext')
            ->willReturnCallback(
                static fn (string $name): Source => new Source($templates[$name], $name, "templates/$name"),
            )
        ;

        $filesystemLoader
            ->method('getCacheKey')
            ->willReturnArgument(0)
        ;

        $filesystemLoader
            ->method('getInheritanceChains')
            ->willReturnCallback(
                static function () use ($templates): array {
                    $hierarchy = ['identifier' => []];

                    foreach (array_keys($templates) as $name) {
                        $hierarchy['identifier']["templates/$name"] = $name;
                    }

                    return $hierarchy;
                },
            )
        ;

        $environment = new Environment($filesystemLoader);
        $cache = new ArrayAdapter();

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $filesystemLoader,
                $this->createMock(ContaoCsrfTokenManager::class),
                $this->createMock(ContaoVariable::class),
                new InspectorNodeVisitor($cache, $environment),
            ),
        );

        return new Inspector($environment, $cache, $filesystemLoader);
    }
}
