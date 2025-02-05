<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\ResponseContext;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

/**
 * @experimental
 */
#[YieldReady]
final class AddNode extends Node implements NodeOutputInterface
{
    public function __construct(string $extensionName, Node $body, string|null $identifier, DocumentLocation $location, int $lineno)
    {
        parent::__construct(
            [
                'body' => $body,
            ],
            [
                'extension_name' => $extensionName,
                'identifier' => $identifier,
                'location' => $location,
            ],
            $lineno,
        );
    }

    /**
     * @todo Remove output buffer handling once Twig is yield-only (probably version 4.0)
     */
    public function compile(Compiler $compiler): void
    {
        /** @see AddNodeTest::testCompilesAddNode() */
        $compiler
            ->write('if ($this->env->isDebug()) { ob_start(); } else { ob_start(static function () { return \'\'; }); }'."\n")
            ->write('try {'."\n")
            ->indent()
            ->write('$__contao_document_content = \'\';'."\n")
            ->write('foreach((function () use (&$context, $macros, $blocks) {'."\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->write("yield '';\n")
            ->outdent()
            ->write('})() as $__contao_document_chunk) {'."\n")
            ->indent()
            ->write('$__contao_document_content .= ob_get_contents() . $__contao_document_chunk;'."\n")
            ->write('ob_clean();'."\n")
            ->outdent()
            ->write('}'."\n")
            ->outdent()
            ->write('} finally { ob_end_clean(); }'."\n")
            ->write('$this->extensions[')
            ->repr($this->getAttribute('extension_name'))
            ->raw(']->addDocumentContent('."\n")
            ->indent()
            ->write('')
            ->repr($this->getAttribute('identifier'))
            ->raw(', $__contao_document_content, ')
            ->raw(\sprintf('\\%s::%s', DocumentLocation::class, $this->getAttribute('location')->name)."\n")
            ->outdent()
            ->write(');'."\n")
        ;
    }
}
