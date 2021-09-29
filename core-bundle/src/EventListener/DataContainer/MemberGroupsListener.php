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

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Callback(table="tl_article", target="fields.groups.options")
 * @Callback(table="tl_content", target="fields.groups.options")
 * @Callback(table="tl_module", target="fields.groups.options")
 * @Callback(table="tl_page", target="fields.groups.options")
 */
class MemberGroupsListener
{
    private Connection $connection;
    private TranslatorInterface $translator;

    public function __construct(Connection $connection, TranslatorInterface $translator)
    {
        $this->connection = $connection;
        $this->translator = $translator;
    }

    public function __invoke(): array
    {
        $options = [-1 => $this->translator->trans('MSC.guests', [], 'contao_default')];
        $groups = $this->connection->fetchAllAssociative('SELECT id, name FROM tl_member_group WHERE tstamp>0 ORDER BY name');

        foreach ($groups as $group) {
            $options[$group['id']] = $group['name'];
        }

        return $options;
    }
}
