<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\Page;

use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\ServiceAnnotation\Page;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Page(contentComposition=false)
 */
class RootPageController extends AbstractController
{
    public function __invoke(PageModel $pageModel): Response
    {
        $nextPage = $this->getNextPage((int) $pageModel->id);

        return $this->redirect($nextPage->getAbsoluteUrl());
    }

    private function getNextPage(int $rootPageId): PageModel
    {
        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->get('contao.framework')->getAdapter(PageModel::class);
        $nextPage = $pageAdapter->findFirstPublishedByPid($rootPageId);

        if ($nextPage instanceof PageModel) {
            return $nextPage;
        }

        if ($this->has('logger')) {
            $this->get('logger')->error(
                'No active page found under root page "'.$rootPageId.'"',
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
            );
        }

        throw new NoActivePageFoundException('No active page found under root page.');
    }
}
