<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext;

use ParagonIE\CSPBuilder\CSPBuilder;

class CspParser
{
    /**
     * @param string $cspString the Content-Security-Policy header content
     */
    public function parse(CSPBuilder $csp, string $cspString): CSPBuilder
    {
        $directives = explode(';', $cspString);

        foreach ($directives as $directive) {
            [$name, $values] = explode(' ', trim($directive), 2) + [null, null];

            if (null === $name) {
                continue;
            }

            if ('upgrade-insecure-requests' === $name) {
                $csp->addDirective('upgrade-insecure-requests');

                continue;
            }

            if (null === $values) {
                continue;
            }

            foreach (explode(' ', $values) as $value) {
                if ('report-to' === $name) {
                    $csp->setReportUri($value);
                } elseif ('report-uri' === $name) {
                    $csp->setReportTo($value);
                } elseif ('require-sri-for' === $name) {
                    $csp->requireSRIFor($value);
                } else {
                    match ($value) {
                        "'none'" => $csp->addDirective($name, false),
                        "'self'" => $csp->setSelfAllowed($name, true),
                        'blob:' => $csp->setBlobAllowed($name, true),
                        'data:' => $csp->setDataAllowed($name, true),
                        'filesystem:' => $csp->setFileSystemAllowed($name, true),
                        'https:' => $csp->setHttpsAllowed($name, true),
                        'mediastream:' => $csp->setMediaStreamAllowed($name, true),
                        "'report-sample'" => $csp->setReportSample($name, true),
                        "'strict-dynamic'" => $csp->setStrictDynamic($name, true),
                        "'unsafe-eval'" => $csp->setAllowUnsafeEval($name, true),
                        "'unsafe-hashes'" => $csp->setAllowUnsafeHashes($name, true),
                        "'unsafe-inline'" => $csp->setAllowUnsafeInline($name, true),
                        default => $csp->addSource($name, $value),
                    };
                }
            }
        }

        return $csp;
    }
}
