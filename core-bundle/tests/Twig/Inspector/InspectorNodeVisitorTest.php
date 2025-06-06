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
use Contao\CoreBundle\Twig\Inheritance\RuntimeThemeDependentExpression;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Inspector\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Twig\Environment;
use Twig\Node\BodyNode;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Nodes;
use Twig\Source;

class InspectorNodeVisitorTest extends TestCase
{
    public function testHasLowPriority(): void
    {
        $inspectorNodeVisitor = new InspectorNodeVisitor(
            $this->createMock(Storage::class),
            $this->createMock(Environment::class),
        );

        $this->assertSame(128, $inspectorNodeVisitor->getPriority());
    }

    #[DataProvider('provideReferenceExpressions')]
    public function testAnalyzesParent(AbstractExpression $parentExpression, string|null $expectedName): void
    {
        $storage = new Storage(new ArrayAdapter());
        $environment = $this->createMock(Environment::class);

        $moduleNode = new ModuleNode(
            new BodyNode(),
            $parentExpression,
            new EmptyNode(),
            new EmptyNode(),
            new EmptyNode(),
            null,
            new Source('…', 'template.html.twig', 'path/to/template.html.twig'),
        );

        (new InspectorNodeVisitor($storage, $environment))->leaveNode($moduleNode, $environment);

        $data = $storage->get('path/to/template.html.twig');
        $this->assertSame($expectedName, $data['parent']);
    }

    #[DataProvider('provideReferenceExpressions')]
    public function testAnalyzesUses(AbstractExpression $useExpression, string|null $expectedName): void
    {
        $storage = new Storage(new ArrayAdapter());
        $environment = $this->createMock(Environment::class);

        $moduleNode = new ModuleNode(
            new BodyNode(),
            null,
            new EmptyNode(),
            new EmptyNode(),
            new Nodes([
                new Nodes(['template' => $useExpression, 'targets' => new Nodes()]),
            ]),
            null,
            new Source('…', 'template.html.twig', 'path/to/template.html.twig'),
        );

        (new InspectorNodeVisitor($storage, $environment))->leaveNode($moduleNode, $environment);

        $data = $storage->get('path/to/template.html.twig');
        $this->assertSame($expectedName, $data['uses'][0][0] ?? null);
    }

    public static function provideReferenceExpressions(): iterable
    {
        yield 'constant' => [
            new ConstantExpression('foo', 0),
            'foo',
        ];

        yield 'runtime theme dependent' => [
            new RuntimeThemeDependentExpression(['foo' => 'foo', '' => 'baz']),
            'baz',
        ];

        yield 'not analyzable' => [
            new class() extends AbstractExpression {},
            null,
        ];
    }
}
