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
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[AsHook('getSystemMessages')]
class PersonalDataPasswordListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
        private readonly Security $security,
    ) {
    }

    public function __invoke(): string|null
    {
        if (!$this->canAccessSettings()) {
            return null;
        }

        $query = <<<'SQL'
            SELECT COUNT(*)
            FROM tl_module
            WHERE
                type = 'personalData'
                AND editable LIKE '%"password"%'
            SQL;

        if ($this->connection->executeQuery($query)->fetchOne() < 1) {
            return null;
        }

        $message = $this->translator->trans('ERR.personalDataPassword', [], 'contao_default');

        return '<p class="tl_error">'.$message.'</p>';
    }

    private function canAccessSettings(): bool
    {
        if (!isset($GLOBALS['BE_MOD']['design']['themes'])) {
            return false;
        }

        return $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'themes')
            && $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_THEME, 'modules');
    }
}
