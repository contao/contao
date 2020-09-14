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

use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ForwardPageController extends AbstractPageController
{
    protected function getResponse(PageModel $pageModel, Request $request): Response
    {
        $forwardPage = $this->getForwardPage($pageModel);

        $queryString = '';
        if (!empty($query = $request->query->all())) {
            $queryString = '?'.http_build_query($query);
        }

        return $this->redirect(
            $forwardPage->getAbsoluteUrl($request->attributes->get('parameters')) . $queryString,
            'temporary' === $pageModel->redirect ? Response::HTTP_SEE_OTHER : Response::HTTP_MOVED_PERMANENTLY
        );
    }

    private function getForwardPage(PageModel $pageModel): PageModel
    {
        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->get('contao.framework')->getAdapter(PageModel::class);

        if ($pageModel->jumpTo) {
            $forwardPage = $pageAdapter->findPublishedById($pageModel->jumpTo);
        } else {
            $forwardPage = $pageAdapter->findFirstPublishedRegularByPid($pageModel->id);
        }

        if ($forwardPage instanceof PageModel) {
            return $forwardPage;
        }

        if (null !== ($logger = $this->get('logger'))) {
            $logger->error(
                'Forward page ID "' . $pageModel->jumpTo . '" does not exist',
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
            );
        }

        throw new ForwardPageNotFoundException('Forward page not found');
    }
}
