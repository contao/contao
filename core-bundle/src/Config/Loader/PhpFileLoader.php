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

        $code = $this->stripLegacyCheck($code);

        if ('namespaced' === $type) {
            $code = sprintf("\nnamespace %s {%s}\n", $namespace, $code);
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

        // Traverse AST to handle namespacing and drop "declare('strict_types')"
        $namespaceResolver = new NameResolver();

        $namespaceStripper = new class() extends NodeVisitorAbstract {
            public function leaveNode(Node $node)
            {
                if ($node instanceof Node\Stmt\Namespace_) {
                    return $node->stmts;
                }

                if ($node instanceof Node\Stmt\Use_) {
                    return NodeTraverser::REMOVE_NODE;
                }

                return null;
            }
        };

        $declareStrictTypesStripper = new class() extends NodeVisitorAbstract {
            public function leaveNode(Node $node)
            {
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

                return null;
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($namespaceResolver);
        $traverser->addVisitor($namespaceStripper);
        $traverser->addVisitor($declareStrictTypesStripper);
        $ast = $traverser->traverse($ast);

        // todo: should we also drop any inline HTML?

        // Emit code and namespace information.
        $prettyPrinter = new PrettyPrinter();
        $code = $prettyPrinter->prettyPrint($ast);
        $namespaceNode = $namespaceResolver->getNameContext()->getNamespace();
        $namespace = null !== $namespaceNode ? $namespaceNode->toString() : '';

        return [$code, $namespace];
    }

    private function stripLegacyCheck(string $code): string
    {
        $code = str_replace(
            [
                "if (!defined('TL_ROOT')) die('You cannot access this file directly!');",
                "if (!defined('TL_ROOT')) die('You can not access this file directly!');",
            ],
            '',
            $code
        );

        return "\n".trim($code)."\n";
    }
}
