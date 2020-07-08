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

use Contao\Backend;
use Contao\BackendUser;
use Contao\CoreBundle\ContentRouting\PageProviderInterface;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\Image;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\ServiceAnnotationBundle\ServiceAnnotationInterface;

class ContentCompositionListener implements ServiceAnnotationInterface
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var ServiceLocator
     */
    private $pageProviders;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Image&Adapter
     */
    private $image;

    /**
     * @var Backend&Adapter
     */
    private $backend;

    public function __construct(ContaoFramework $framework, Security $security, ServiceLocator $pageProviders, TranslatorInterface $translator, Connection $connection, RequestStack $requestStack)
    {
        $this->framework = $framework;
        $this->security = $security;
        $this->pageProviders = $pageProviders;
        $this->translator = $translator;
        $this->connection = $connection;
        $this->requestStack = $requestStack;

        $this->image = $this->framework->getAdapter(Image::class);
        $this->backend = $this->framework->getAdapter(Backend::class);
    }

    /**
     * @Callback(table="tl_page", target="list.operations.articles.button")
     */
    public function renderPageArticlesOperation(array $row, string $href, string $label, string $title, string $icon): string
    {
        if (!$this->security->isGranted('contao_user.modules', 'article')) {
            return '';
        }

        $pageModel = $this->framework->createInstance(PageModel::class);
        $pageModel->preventSaving(false);
        $pageModel->setRow($row);

        if (!$this->supportsComposition($pageModel) || !$this->hasArticlesInLayout($pageModel)) {
            return $this->image->getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
        }

        return sprintf(
            '<a href="%s" title="%s">%s</a> ',
            $this->backend->addToUrl($href.'&amp;pn='.$row['id']),
            StringUtil::specialchars($title),
            $this->image->getHtml($icon, $label)
        );
    }

    /**
     * Automatically create an article in the main column of a new page.
     *
     * @Callback(table="tl_page", target="config.onsubmit")
     */
    public function generateArticleForPage(DataContainer $dc): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $user = $this->security->getUser();

        // Return if there is no active record (override all)
        if (!$dc->activeRecord || null === $request || !$request->hasSession() || !$user instanceof BackendUser) {
            return;
        }

        $pageModel = $this->framework->createInstance(PageModel::class);
        $pageModel->preventSaving(false);
        $pageModel->setRow((array) $dc->activeRecord);

        if (
            empty($pageModel->title)
            || !$this->supportsComposition($pageModel)
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
        $total = $this->connection->executeQuery(
            'SELECT COUNT(*) AS count FROM tl_article WHERE pid=:pid',
            ['pid' => $dc->id]
        )->fetchColumn();

        if ($total > 0) {
            return;
        }

        // Create article
        $article = [
            'pid' => $dc->id,
            'sorting' => 128,
            'tstamp' => time(),
            'author' => $user->id,
            'inColumn' => $column,
            'title' => $dc->activeRecord->title,
            'alias' => str_replace('/', '-', $dc->activeRecord->alias), // see #516
            'published' => $dc->activeRecord->published,
        ];

        $this->connection->insert('tl_article', $article);
    }

    /**
     * @Callback(table="tl_article", target="list.sorting.paste_button")
     */
    public function renderArticlePasteButton(DataContainer $dc, array $row, string $table, bool $cr, array $clipboard = null): string
    {
        // TODO: remove once https://github.com/contao/contao/pull/1864 is merged
        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return '';
        }

        if ($table === $GLOBALS['TL_DCA'][$dc->table]['config']['ptable']) {
            return $this->renderArticlePasteIntoButton($dc, $row, $cr, $clipboard);
        }

        return $this->renderArticlePasteAfterButton($dc, $row, $cr, $clipboard);
    }

    private function renderArticlePasteIntoButton(DataContainer $dc, array $row, bool $cr, array $clipboard = null)
    {
        $pageModel = $this->framework->createInstance(PageModel::class);
        $pageModel->preventSaving(false);
        $pageModel->setRow($row);

        // Do not show paste button for pages without content composition or articles in layout
        if (!$this->supportsComposition($pageModel) || !$this->hasArticlesInLayout($pageModel)) {
            return '';
        }

        // TODO: use Security::isGranted() when https://github.com/contao/contao/pull/1864 is merged
        /** @var BackendUser $user */
        $user = $this->security->getUser();

        if ($cr || !$user->isAllowed(BackendUser::CAN_EDIT_ARTICLE_HIERARCHY, $row)) {
            return $this->image->getHtml('pasteinto_.svg').' ';
        }

        return sprintf(
            '<a href="%s" title="%s" onclick="Backend.getScrollOffset()">%s</a> ',
            $this->backend->addToUrl('act='.$clipboard['mode'].'&amp;mode=2&amp;pid='.$row['id'].(!\is_array($clipboard['id'] ?? null) ? '&amp;id='.$clipboard['id'] : '')),
            StringUtil::specialchars($this->translator->trans($dc->table.'.pasteinto.1', [$row['id']], 'contao_'.$dc->table)),
            $this->image->getHtml(
                'pasteinto.svg',
                $this->translator->trans($dc->table.'.pasteinto.1', [$row['id']], 'contao_'.$dc->table)
            )
        );
    }

    private function renderArticlePasteAfterButton(DataContainer $dc, array $row, bool $cr, array $clipboard = null)
    {
        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->framework->getAdapter(PageModel::class);

        $pageModel = $pageAdapter->findByPk($row['pid']);

        // Do not show paste button for pages without content composition or articles in layout
        if (null === $pageModel || !$this->supportsComposition($pageModel) || !$this->hasArticlesInLayout($pageModel)) {
            return '';
        }

        // TODO: use Security::isGranted() when https://github.com/contao/contao/pull/1864 is merged
        /** @var BackendUser $user */
        $user = $this->security->getUser();

        if (
            ('cut' === $clipboard['mode'] && $clipboard['id'] === $row['id'])
            || ('cutAll' === $clipboard['mode'] && \in_array($row['id'], $clipboard['id'], true))
            || !$user->isAllowed(BackendUser::CAN_EDIT_ARTICLE_HIERARCHY, $pageModel->row())
            || $cr
        ) {
            return $this->image->getHtml('pasteafter_.svg').' ';
        }

        return sprintf(
            '<a href="%s" title="%s" onclick="Backend.getScrollOffset()">%s</a> ',
            $this->backend->addToUrl('act='.$clipboard['mode'].'&amp;mode=1&amp;pid='.$row['id'].(!\is_array($clipboard['id']) ? '&amp;id='.$clipboard['id'] : '')),
            StringUtil::specialchars($this->translator->trans($dc->table.'.pasteafter.1', [$row['id']], 'contao_'.$dc->table)),
            $this->image->getHtml(
                'pasteafter.svg',
                $this->translator->trans($dc->table.'.pasteafter.1', [$row['id']], 'contao_'.$dc->table)
            )
        );
    }

    private function supportsComposition(PageModel $pageModel): bool
    {
        if (!$this->pageProviders->has($pageModel->type)) {
            return true;
        }

        /** @var PageProviderInterface $provider */
        $provider = $this->pageProviders->get($pageModel->type);

        return $provider->supportsContentComposition($pageModel);
    }

    private function hasArticlesInLayout(PageModel $pageModel): bool
    {
        return null !== $this->getArticleColumnInLayout($pageModel);
    }

    private function getArticleColumnInLayout(PageModel $pageModel): ?string
    {
        $pageModel->loadDetails();

        /** @var LayoutModel $layout */
        $layout = $pageModel->getRelated('layout');

        if (null === $layout) {
            return null;
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
