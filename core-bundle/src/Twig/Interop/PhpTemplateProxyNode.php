<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Interop;

use Twig\Compiler;
use Twig\Node\Node;

class PhpTemplateProxyNode extends Node
{
    public function __construct(string $extensionName)
    {
        parent::__construct([], [
            'extension_name' => $extensionName,
        ]);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->write('echo $this->extensions[')
            ->repr($this->getAttribute('extension_name'))
            ->raw(']->renderLegacyTemplate($this->getTemplateName(), $blocks, $context);')
            ->raw("\n")
        ;
    }
}
