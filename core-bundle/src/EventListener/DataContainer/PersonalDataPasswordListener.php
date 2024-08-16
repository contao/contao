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
use Doctrine\DBAL\Connection;
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
    ) {
    }

    public function __invoke(): string|null
    {
        $count = $this->connection
            ->executeQuery("
                SELECT COUNT(*)
                FROM tl_module
                WHERE
                    type = 'personalData'
                    AND editable LIKE '%\"password\"%'
            ")
            ->fetchOne()
        ;

        if ($count < 1) {
            return null;
        }

        $message = $this->translator->trans('ERR.personalDataPassword', [], 'contao_default');

        return '<p class="tl_error">' . $message . '</p>';
    }
}
