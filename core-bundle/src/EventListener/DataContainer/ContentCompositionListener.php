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

use Contao\BackendUser;
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\DataContainer;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class ContentCompositionListener
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Security $security,
        private readonly PageRegistry $pageRegistry,
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[AsCallback(table: 'tl_page', target: 'list.operations.articles.button')]
    public function renderPageArticlesOperation(DataContainerOperation $operation): void
    {
        if (!$this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'article')) {
            $operation->setHtml('');

            return;
        }

        $pageModel = $this->framework->createInstance(PageModel::class);
        $pageModel->preventSaving(false);
        $pageModel->setRow($operation->getRecord());

        if (!$this->pageRegistry->supportsContentComposition($pageModel) || !$this->hasArticlesInLayout($pageModel)) {
            $operation->disable();
        } else {
            $operation['href'] .= '&amp;pn='.$operation->getRecord()['id'];
        }
    }

    /**
     * Automatically creates an article in the main column of a new page.
     */
    #[AsCallback(table: 'tl_page', target: 'config.onsubmit', priority: -16)]
    public function generateArticleForPage(DataContainer $dc): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $user = $this->security->getUser();
        $currentRecord = $dc->getCurrentRecord();

        // Return if there is no current record (override all)
        if (null === $currentRecord || !$request || !$user instanceof BackendUser || !$request->hasSession()) {
            return;
        }

        $pageModel = $this->framework->createInstance(PageModel::class);
        $pageModel->preventSaving(false);
        $pageModel->setRow($currentRecord);

        if (
            empty($pageModel->title)
            || !$this->pageRegistry->supportsContentComposition($pageModel)
            || null === ($column = $this->getArticleColumnInLayout($pageModel))
        ) {
            return;
        }

        $sessionBag = $request->getSession()->getBag('contao_backend');

        if (!$sessionBag instanceof AttributeBagInterface) {
            return;
        }

        $new_records = $sessionBag->get('new_records');

        // Not a new page
        if (!$new_records || !\is_array($new_records[$dc->table] ?? null) || !\in_array($dc->id, $new_records[$dc->table], true)) {
            return;
        }

        // Check whether there are articles (e.g. on copied pages)
        $total = $this->connection->fetchOne('SELECT COUNT(*) FROM tl_article WHERE pid=:pid', ['pid' => $dc->id]);

        if ($total > 0) {
            return;
        }

        $article = [
            'pid' => $dc->id,
            'sorting' => 128,
            'tstamp' => time(),
            'author' => $user->id,
            'inColumn' => $column,
            'title' => $currentRecord['title'] ?? null,
            'alias' => str_replace('/', '-', $currentRecord['alias'] ?? ''), // see #516
            'published' => $currentRecord['published'] ?? null,
        ];

        if (!$this->security->isGranted(ContaoCorePermissions::DC_PREFIX.'tl_article', new CreateAction('tl_article', $article))) {
            return;
        }

        $this->connection->insert('tl_article', $article);
    }

    private function hasArticlesInLayout(PageModel $pageModel): bool
    {
        return null !== $this->getArticleColumnInLayout($pageModel);
    }

    private function getArticleColumnInLayout(PageModel $pageModel): string|null
    {
        $pageModel->loadDetails();

        $layout = $pageModel->getRelated('layout');

        if (!$layout instanceof LayoutModel) {
            return 'main';
        }

        $columns = [];

        foreach (StringUtil::deserialize($layout->modules, true) as $config) {
            if (0 === (int) $config['mod']) {
                $columns[] = $config['col'];
            }
        }

        $columns = array_filter(array_unique($columns));

        if (empty($columns)) {
            return null;
        }

        if (\in_array('main', $columns, true)) {
            return 'main';
        }

        return reset($columns);
    }
}
