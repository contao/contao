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

use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RootPageController extends AbstractPageController
{
    public function getResponse(PageModel $pageModel, Request $request): Response
    {
        return $this->redirect(
            $this->getNextPage((int) $pageModel->id)->getAbsoluteUrl(),
            Response::HTTP_SEE_OTHER
        );
    }

    private function getNextPage(int $rootPageId): PageModel
    {
        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->get('contao.framework')->getAdapter(PageModel::class);
        $nextPage = $pageAdapter->findFirstPublishedByPid($rootPageId);

        if ($nextPage instanceof PageModel) {
            return $nextPage;
        }

        if (null !== ($logger = $this->get('logger'))) {
            $logger->error(
                'No active page found under root page "'.$rootPageId.'"',
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
            );
        }

        throw new NoActivePageFoundException('No active page found under root page.');
    }
}
