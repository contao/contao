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

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Symfony\Bundle\SecurityBundle\Security;

#[AsCallback(table: 'tl_theme', target: 'list.operations.modules.button')]
#[AsCallback(table: 'tl_theme', target: 'list.operations.layout.button')]
#[AsCallback(table: 'tl_theme', target: 'list.operations.imageSizes.button')]
class ThemeOperationsListener
{
    public function __construct(private readonly Security $security)
    {
    }

    public function __invoke(DataContainerOperation $operation): void
    {
        parse_str($operation['href'] ?? '', $result);

        $isGranted = match ($result['table'] ?? null) {
            'tl_module' => $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_FRONTEND_MODULES),
            'tl_layout' => $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_LAYOUTS),
            'tl_image_size' => $this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_IMAGE_SIZES),
            default => true,
        };

        if (!$isGranted) {
            $operation->disable();
        }
    }
}
