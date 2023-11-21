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

use Twig\Compiler;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

/**
 * @experimental
 */
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

    public function compile(Compiler $compiler): void
    {
        // if ($this->env->isDebug()) { ob_start(); } else { ob_start(static function () { return ''; }); }
        // try {
        //     <sub-compiled content>
        //     $__contao_document_content = ob_get_contents();
        // } finally { ob_end_clean(); }
        // $this->extensions["Contao\\…\\ContaoExtension"]->addDocumentContent(
        //     '<identifier>', $__contao_document_content, Contao\…\DocumentLocation::<location>
        // );
        $compiler
            ->write('if ($this->env->isDebug()) { ob_start(); } else { ob_start(static function () { return \'\'; }); }'."\n")
            ->write('try {'."\n")
            ->indent()
            ->subcompile($this->getNode('body'))
            ->write('$__contao_document_content = ob_get_contents();'."\n")
            ->outdent()
            ->write('} finally { ob_end_clean(); }'."\n")
            ->write('$this->extensions[')
            ->repr($this->getAttribute('extension_name'))
            ->raw(']->addDocumentContent('."\n")
            ->indent()
            ->write('')
            ->repr($this->getAttribute('identifier'))
            ->raw(', $__contao_document_content, ')
            ->raw(sprintf('\\%s::%s', DocumentLocation::class, $this->getAttribute('location')->name)."\n")
            ->outdent()
            ->write(');'."\n")
        ;
    }
}
