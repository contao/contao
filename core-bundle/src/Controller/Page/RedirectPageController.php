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
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Util\UrlUtil;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsPage('redirect', '')]
class RedirectPageController
{
    public function __construct(private readonly InsertTagParser $insertTagParser)
    {
    }

    public function __invoke(Request $request, PageModel $pageModel): Response
    {
        $status = 'temporary' === $pageModel->redirect ? Response::HTTP_SEE_OTHER : Response::HTTP_MOVED_PERMANENTLY;
        $url = $this->insertTagParser->replaceInline($pageModel->url);
        $url = UrlUtil::makeAbsolute($url, $request->getUriForPath('/'));

        return new RedirectResponse($url, $status);
    }
}
