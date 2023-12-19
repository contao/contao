<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Csp;

use Nelmio\SecurityBundle\ContentSecurityPolicy\ContentSecurityPolicyParser;
use Nelmio\SecurityBundle\ContentSecurityPolicy\DirectiveSet;
use Nelmio\SecurityBundle\ContentSecurityPolicy\PolicyManager;

class CspParser
{
    public function __construct(private readonly PolicyManager $policyManager)
    {
    }

    public function parseHeader(string $header): DirectiveSet
    {
        $parser = new ContentSecurityPolicyParser();
        $directiveSet = new DirectiveSet($this->policyManager);
        $names = $directiveSet->getNames();

        $directives = explode(';', $header);

        foreach ($directives as $directive) {
            [$name, $value] = explode(' ', trim($directive), 2) + [null, null];

            if (null === $value && DirectiveSet::TYPE_NO_VALUE === ($names[$name] ?? null)) {
                $value = true;
            }

            $directiveSet->setDirective($name, $parser->parseSourceList(explode(' ', (string) $value)));
        }

        return $directiveSet;
    }
}
