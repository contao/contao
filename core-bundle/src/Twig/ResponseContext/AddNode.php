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

use Contao\CoreBundle\Twig\Runtime\HtmlDocumentRuntime;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeCaptureInterface;

/**
 * @internal
 */
#[YieldReady]
final class AddNode extends Node implements NodeCaptureInterface
{
    /**
     * @param array{identifier: string|null, location: DocumentLocation, position: string|null, reference: string|null} $attributes
     */
    public function __construct(Node $body, array $attributes, int $lineno)
    {
        parent::__construct(
            [
                'body' => $body,
            ],
            $attributes,
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
            ->write('$this->env->getRuntime(')
            ->repr(HtmlDocumentRuntime::class)
            ->raw(')->add('."\n")
            ->indent()
            ->write('$__contao_document_content, ')
            ->raw(\sprintf('\\%s::%s, ', DocumentLocation::class, $this->getAttribute('location')->name))
            ->repr($this->getOptions())
            ->raw("\n")
            ->outdent()
            ->write(');'."\n")
        ;
    }

    /**
     * @return array{identifier?: string, before?: string, after?: string}
     */
    private function getOptions(): array
    {
        $options = [];

        if (null !== $identifier = $this->getAttribute('identifier')) {
            $options['identifier'] = $identifier;
        }

        $position = $this->getAttribute('position');
        $reference = $this->getAttribute('reference');

        if ('before' === $position && null !== $reference) {
            $options['before'] = $reference;
        } elseif ('after' === $position && null !== $reference) {
            $options['after'] = $reference;
        }

        return $options;
    }
}
