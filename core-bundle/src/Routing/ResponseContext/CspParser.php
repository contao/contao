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
     * @param string $cspString The Content-Security-Policy header content.
     */
    public function fromCspHeader(string $cspString): CSPBuilder
    {
        $csp = new CSPBuilder();

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
                    switch ($value) {
                        case "'none'": $csp->addDirective($name, false); break;
                        case "'self'": $csp->setSelfAllowed($name, true); break;
                        case 'blob:': $csp->setBlobAllowed($name, true); break;
                        case 'data:': $csp->setDataAllowed($name, true); break;
                        case 'filesystem:': $csp->setFileSystemAllowed($name, true); break;
                        case 'https:': $csp->setHttpsAllowed($name, true); break;
                        case 'mediastream:': $csp->setMediaStreamAllowed($name, true); break;
                        case "'report-sample'": $csp->setReportSample($name, true); break;
                        case "'strict-dynamic'": $csp->setStrictDynamic($name, true); break;
                        case "'unsafe-eval'": $csp->setAllowUnsafeEval($name, true); break;
                        case "'unsafe-hashes'": $csp->setAllowUnsafeHashes($name, true); break;
                        case "'unsafe-inline'": $csp->setAllowUnsafeInline($name, true); break;

                        default: $csp->addSource($name, $value);
                    }
                }
            }
        }

        return $csp;
    }
}
