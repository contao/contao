<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\NewsBundle\Controller\Page\NewsFeedController;
use Contao\NewsBundle\Security\ContaoNewsPermissions;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PageListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    #[AsCallback('tl_page', target: 'fields.newsArchives.options')]
    public function getAllowedArchives(): array
    {
        $archives = $this->connection->createQueryBuilder()
            ->select('id, title')
            ->from('tl_news_archive')
            ->fetchAllKeyValue()
        ;

        foreach (array_keys($archives) as $id) {
            if (!$this->authorizationChecker->isGranted(ContaoNewsPermissions::USER_CAN_EDIT_ARCHIVE, (int) $id)) {
                unset($archives[$id]);
            }
        }

        return $archives;
    }

    #[AsHook('getPageStatusIcon')]
    public function getStatusIcon(object $page, string $image): string
    {
        if (NewsFeedController::TYPE !== $page->type) {
            return $image;
        }

        return str_replace('regular', 'feed', $image);
    }
}
