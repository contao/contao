<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\MemberGroupModel;

/**
 * Filter disabled groups in the front end (see #6757).
 */
#[AsHook('loadDataContainer')]
class ActiveMemberGroupsListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    public function __invoke(string $table): void
    {
        if ('tl_member' !== $table || $this->scopeMatcher->isBackendRequest()) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_member']['fields']['groups']['options_callback'] = $this->getActiveGroups(...);
    }

    private function getActiveGroups(): array
    {
        $memberGroupAdapter = $this->framework->getAdapter(MemberGroupModel::class);

        return $memberGroupAdapter->findAllActive()?->fetchEach('name') ?? [];
    }
}
