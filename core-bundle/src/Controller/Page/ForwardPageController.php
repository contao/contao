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

use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsPage('forward', '')]
#[AsPage('forward_params', '{params}', defaults: ['params' => '', '_forward_params' => true], requirements: ['params' => '(.+?)?'], contentComposition: false)]
class ForwardPageController
{
    public function __construct(private readonly LoggerInterface|null $logger = null)
    {
    }

    public function __invoke(Request $request, PageModel $pageModel, string $params): Response
    {
        $status = 'temporary' === $pageModel->redirect ? Response::HTTP_SEE_OTHER : Response::HTTP_MOVED_PERMANENTLY;

        return new RedirectResponse($this->getForwardUrl($request, $pageModel, $params), $status);
    }

    private function getForwardUrl(Request $request, PageModel $pageModel, string $pathParams = ''): string
    {
        if ($pageModel->jumpTo) {
            $nextPage = PageModel::findPublishedById($pageModel->jumpTo);
        } else {
            $nextPage = PageModel::findFirstPublishedRegularByPid($pageModel->id);
        }

        // Forward page does not exist
        if (!$nextPage instanceof PageModel) {
            $this->logger?->error('Forward page ID "'.$pageModel->jumpTo.'" does not exist');

            throw new ForwardPageNotFoundException('Forward page not found');
        }

        if (!$request->attributes->get('_forward_params', false)) {
            return $nextPage->getAbsoluteUrl();
        }

        return $nextPage->getAbsoluteUrl('/'.$pathParams).$request->getQueryString();
    }
}
