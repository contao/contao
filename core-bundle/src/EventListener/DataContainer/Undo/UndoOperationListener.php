<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\DataContainer\Undo;

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\StringUtil;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @internal
 */
class UndoOperationListener
{
    public function __construct(private readonly Security $security)
    {
    }

    #[AsCallback(table: 'tl_undo', target: 'list.operations.undo.button')]
    public function __invoke(DataContainerOperation $operation): void
    {
        $data = StringUtil::deserialize($operation->getRecord()['data'] ?? null);
        $table = $operation->getRecord()['fromTable'];
        $row = $data[$table][0] ?? null;

        if (
            $row
            && $this->security->isGranted(ContaoCorePermissions::DC_PREFIX.$table, new CreateAction($table, $row))
        ) {
            return;
        }

        $operation->disable();
    }
}
