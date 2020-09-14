<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Config\Loader;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
use Symfony\Component\Config\Loader\Loader;
use Webmozart\PathUtil\Path;

/**
 * Reads PHP files and returns the content without the opening and closing PHP tags.
 */
class PhpFileLoader extends Loader
{
    public function load($resource, $type = null): string
    {
        [$code, $namespace] = $this->parseFile((string) $resource);

        if ('namespaced' === $type) {
            $code = sprintf("\nnamespace %s{%s}\n", ltrim($namespace.' '), $code);
        }

        return $code;
    }

    public function supports($resource, $type = null): bool
    {
        return 'php' === Path::getExtension((string) $resource, true);
    }

    /**
     * Parses a file and returns the code and namespace.
     *
     * @return array<string|false>
     */
    private function parseFile(string $file): array
    {
        // Parse input into an AST.
        $contents = trim(file_get_contents($file));
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($contents);

        // Traverse and modify the AST.
        $namespaceResolver = new NameResolver();

        $nodeStripper = new class() extends NodeVisitorAbstract {
            public function leaveNode(Node $node)
            {
                // Drop namespace and use declarations.
                if ($node instanceof Node\Stmt\Namespace_) {
                    return $node->stmts;
                }

                if ($node instanceof Node\Stmt\Use_) {
                    return NodeTraverser::REMOVE_NODE;
                }

                // Drop 'strict_types' definition.
                if ($node instanceof Node\Stmt\Declare_) {
                    foreach ($node->declares as $key => $declare) {
                        if ('strict_types' === $declare->key->name) {
                            unset($node->declares[$key]);
                        }
                    }

                    if (empty($node->declares)) {
                        return NodeTraverser::REMOVE_NODE;
                    }
                }

                // Drop any inline HTML.
                if ($node instanceof Node\Stmt\InlineHTML) {
                    return NodeTraverser::REMOVE_NODE;
                }

                // Drop legacy access check.
                if ($this->matchLegacyCheck($node)) {
                    return NodeTraverser::REMOVE_NODE;
                }

                return null;
            }

            private function matchLegacyCheck(Node $node): bool
            {
                return $node instanceof Node\Stmt\If_ &&
                    // match "if(!defined('TL_ROOT'))"
                    ($condition = $node->cond) instanceof Node\Expr\BooleanNot &&
                    $condition->expr instanceof Node\Expr\FuncCall &&
                    $condition->expr->name instanceof Node\Name &&
                    'defined' === $condition->expr->name->toLowerString() &&
                    null !== ($argument = $condition->expr->args[0] ?? null) &&
                    $argument->value instanceof Node\Scalar\String_ &&
                    'TL_ROOT' === $argument->value->value &&

                    // match "die('You ...')"
                    ($statement = $node->stmts[0] ?? null) instanceof Node\Stmt\Expression &&
                    $statement->expr instanceof Node\Expr\Exit_ &&
                    ($text = $statement->expr->expr) instanceof Node\Scalar\String_ &&
                    \in_array($text->value, [
                        'You cannot access this file directly!',
                        'You can not access this file directly!',
                    ], true);
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($namespaceResolver);
        $traverser->addVisitor($nodeStripper);

        $ast = $traverser->traverse($ast);

        // Emit code and namespace information.
        $prettyPrinter = new PrettyPrinter();
        $code = sprintf("\n%s\n", $prettyPrinter->prettyPrint($ast));
        $namespaceNode = $namespaceResolver->getNameContext()->getNamespace();
        $namespace = null !== $namespaceNode ? $namespaceNode->toString() : '';

        return [$code, $namespace];
    }
}
