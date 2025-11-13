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
use Twig\Node\BlockNode;
use Twig\Node\BlockReferenceNode;
use Twig\Node\BodyNode;
use Twig\Node\EmptyNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Nodes;
use Twig\NodeTraverser;
use Twig\Source;
use Twig\Token;
use Twig\TokenStream;

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

    public function testAnalyzesBlockNesting(): void
    {
        $storage = new Storage(new ArrayAdapter());

        $environment = $this->createMock(Environment::class);
        $environment
            ->method('tokenize')
            ->willReturn(new TokenStream([new Token(Token::EOF_TYPE, null, 0)]))
        ;

        $moduleNode = new ModuleNode(
            new BodyNode([
                new BlockReferenceNode('foo', 0),
            ]),
            null,
            new Nodes([
                'foo' => new BodyNode([
                    new BlockNode(
                        'foo',
                        new Nodes([
                            new BlockReferenceNode('bar', 0),
                        ]),
                        0,
                    ),
                ]),
                'bar' => new BodyNode([
                    new BlockNode(
                        'bar',
                        new Nodes([
                            new BlockReferenceNode('baz', 0),
                        ]),
                        0,
                    ),
                ]),
                'baz' => new BodyNode([
                    new BlockNode('baz', new EmptyNode(), 0),
                ]),
            ]),
            new EmptyNode(),
            new EmptyNode(),
            null,
            new Source('…', 'template.html.twig', 'path/to/template.html.twig'),
        );

        $inspectorNodeVisitor = new InspectorNodeVisitor($storage, $environment);

        $nodeTraverser = new NodeTraverser($environment, [$inspectorNodeVisitor]);
        $nodeTraverser->traverse($moduleNode);

        $data = $storage->get('path/to/template.html.twig');

        $this->assertSame(
            [
                'foo' => null,
                'bar' => 'foo',
                'baz' => 'bar',
            ],
            $data['nesting'],
        );
    }
}
