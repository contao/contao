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
use Contao\Input;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsPage('forward', '')]
#[AsPage('forward_params', '{params?}', defaults: ['_forward_params' => true], contentComposition: false)]
class ForwardPageController
{
    public function __construct(private readonly LoggerInterface|null $logger = null)
    {
    }

    public function __invoke(Request $request, PageModel $pageModel): Response
    {
        $status = 'temporary' === $pageModel->redirect ? Response::HTTP_SEE_OTHER : Response::HTTP_MOVED_PERMANENTLY;

        return new RedirectResponse($this->getForwardUrl($request, $pageModel), $status);
    }

    private function getForwardUrl(Request $request, PageModel $pageModel): string
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

        $pathParams = '';
        $queryString = $request->getQueryString();
        $queryParams = [];

        // Extract the query string keys (see #5867)
        if ($queryString) {
            $arrChunks = explode('&', $queryString);

            foreach ($arrChunks as $strChunk) {
                [$k] = explode('=', $strChunk, 2);
                $queryParams[] = $k;
            }
        }

        // Add $_GET parameters
        foreach (Input::getKeys() as $key) {
            if ('language' === $key) {
                continue;
            }

            // Ignore arrays (see #4895)
            if (\is_array($_GET[$key])) {
                continue;
            }

            // Ignore the query string parameters (see #5867)
            if (\in_array($key, $queryParams, true)) {
                continue;
            }

            // Ignore the auto_item parameter (see #5886)
            if ('auto_item' === $key) {
                $pathParams .= '/'.Input::get($key);
            } else {
                $pathParams .= '/'.$key.'/'.Input::get($key);
            }
        }

        // Append the query string (see #5867)
        if ($queryString) {
            $queryString = '?'.$queryString;
        }

        return $nextPage->getAbsoluteUrl($pathParams).$queryString;
    }
}
