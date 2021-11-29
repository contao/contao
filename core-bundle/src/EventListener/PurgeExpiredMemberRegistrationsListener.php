<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\MemberModel;

/**
 * Purges the member registrations in the front and back end, whenever tl_member is loaded (#3711).
 *
 * @internal
 *
 * @Hook("reviseTable")
 */
class PurgeExpiredMemberRegistrationsListener
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function __invoke(string $table): ?bool
    {
        if ('tl_member' !== $table) {
            return null;
        }

        /** @var MemberModel $memberModel */
        $memberModel = $this->framework->getAdapter(MemberModel::class);

        foreach ($memberModel->findExpiredRegistrations() ?? [] as $member) {
            $member->delete();
        }

        return null;
    }
}
