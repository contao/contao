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

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Message;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCallback(table: 'tl_module', target: 'config.onload')]
class PersonalDataPasswordListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request?->query->has('act') && 'select' !== $request->query->get('act')) {
            return;
        }

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
            return;
        }

        $messages = $this->framework->getAdapter(Message::class);
        $messages->addError($this->translator->trans('ERR.personalDataPassword', [], 'contao_default'));
    }
}
