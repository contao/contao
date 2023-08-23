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
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\InlineHTML;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Filesystem\Path;

/**
 * Reads PHP files and returns the content without the opening and closing PHP tags.
 */
class PhpFileLoader extends Loader
{
    public function load(mixed $resource, string|null $type = null): string
    {
        [$code, $namespace] = $this->parseFile((string) $resource);

        if ('namespaced' === $type) {
            $code = sprintf("\nnamespace %s{%s}\n", ltrim($namespace.' '), $code);
        }

        return $code;
    }

    public function supports(mixed $resource, string|null $type = null): bool
    {
        return 'php' === Path::getExtension((string) $resource, true);
    }

    /**
     * Parses a file and returns the code and namespace.
     *
     * @return array{0: string, 1: string}
     */
    private function parseFile(string $file): array
    {
        $content = file_get_contents($file);

        if (false === $content) {
            throw new \InvalidArgumentException(sprintf('Cannot read file "%s".', $file));
        }

        $ast = (new ParserFactory())
            ->create(ParserFactory::PREFER_PHP7)
            ->parse(trim($content))
        ;

        $namespaceResolver = new NameResolver();

        $nodeStripper = new class() extends NodeVisitorAbstract {
            public function leaveNode(Node $node): array|int|null
            {
                // Drop namespace and use declarations
                if ($node instanceof Namespace_) {
                    return $node->stmts;
                }

                if ($node instanceof Use_) {
                    return NodeTraverser::REMOVE_NODE;
                }

                // Drop the "strict_types" definition
                if ($node instanceof Declare_) {
                    foreach ($node->declares as $key => $declare) {
                        if ('strict_types' === $declare->key->name) {
                            unset($node->declares[$key]);
                        }
                    }

                    if (empty($node->declares)) {
                        return NodeTraverser::REMOVE_NODE;
                    }
                }

                // Drop any inline HTML
                if ($node instanceof InlineHTML) {
                    return NodeTraverser::REMOVE_NODE;
                }

                // Drop legacy access check
                if ($this->matchLegacyCheck($node)) {
                    return NodeTraverser::REMOVE_NODE;
                }

                return null;
            }

            private function matchLegacyCheck(Node $node): bool
            {
                return $node instanceof If_
                    // match "if(!defined('TL_ROOT'))"
                    && ($condition = $node->cond) instanceof BooleanNot
                    && $condition->expr instanceof FuncCall
                    && $condition->expr->name instanceof Name
                    && 'defined' === $condition->expr->name->toLowerString()
                    && null !== ($argument = $condition->expr->args[0] ?? null)
                    && $argument->value instanceof String_
                    && 'TL_ROOT' === $argument->value->value

                    // match "die('You ...')"
                    && ($statement = $node->stmts[0] ?? null) instanceof Expression
                    && $statement->expr instanceof Exit_
                    && ($text = $statement->expr->expr) instanceof String_
                    && \in_array($text->value, ['You cannot access this file directly!', 'You can not access this file directly!'], true);
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($namespaceResolver);
        $traverser->addVisitor($nodeStripper);

        $ast = $traverser->traverse($ast);

        // Emit code and namespace information
        $prettyPrinter = new PrettyPrinter();
        $code = sprintf("\n%s\n", $prettyPrinter->prettyPrint($ast));
        $namespaceNode = $namespaceResolver->getNameContext()->getNamespace();
        $namespace = $namespaceNode ? $namespaceNode->toString() : '';

        // Force GC collection to reduce the total memory required when building the cache (see #4069)
        gc_collect_cycles();

        return [$code, $namespace];
    }
}
