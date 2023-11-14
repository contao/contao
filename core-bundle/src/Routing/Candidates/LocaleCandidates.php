<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Candidates;

use Contao\CoreBundle\Routing\Page\PageRegistry;
use Symfony\Component\HttpFoundation\Request;

class LocaleCandidates extends AbstractCandidates
{
    private bool $initialized = false;

    public function __construct(private readonly PageRegistry $pageRegistry)
    {
        parent::__construct([''], []);
    }

    #[\Override]
    public function getCandidates(Request $request): array
    {
        $this->initialize();

        return parent::getCandidates($request);
    }

    /**
     * Lazy-initialize because we do not want to query the database when creating the service.
     */
    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->initialized = true;
        $this->urlSuffixes = $this->pageRegistry->getUrlSuffixes();
    }
}
