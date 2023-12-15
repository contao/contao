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

        if (!\is_array($data)) {
            $operation->disable();

            return;
        }

        foreach ($data as $table => $fields) {
            foreach ($fields as $row) {
                if (!$this->security->isGranted(ContaoCorePermissions::DC_PREFIX.$table, new CreateAction($table, $row))) {
                    $operation->disable();

                    return;
                }
            }
        }
    }
}
