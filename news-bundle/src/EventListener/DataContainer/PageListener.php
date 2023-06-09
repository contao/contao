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

use Contao\BackendUser;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\DataContainer;
use Contao\NewsBundle\Controller\Page\NewsFeedController;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Security;

class PageListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Security $security,
    ) {
    }

    #[AsCallback('tl_page', target: 'config.onload')]
    public function onLoad(DataContainer $dc): void
    {
        $type = $dc->getCurrentRecord()['type'] ?? null;

        if (!$type || NewsFeedController::TYPE !== $type[0]) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_page']['fields']['hide']['eval']['tl_class'] = 'clr w50';
    }

    #[AsCallback('tl_page', target: 'fields.newsArchives.options')]
    public function getAllowedArchives(): array
    {
        $user = $this->security->getUser();

        $qb = $this->connection->createQueryBuilder()
            ->select('id, title')
            ->from('tl_news_archive')
        ;

        if ($user instanceof BackendUser && !$this->security->isGranted('ROLE_ADMIN')) {
            $qb->where($qb->expr()->in('id', $user->news));
        }

        $results = $qb->executeQuery();

        $options = [];

        foreach ($results->fetchAllAssociative() as $archive) {
            $options[$archive['id']] = $archive['title'];
        }

        return $options;
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
