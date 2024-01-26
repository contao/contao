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
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
#[AsPage(path: '', contentComposition: false)]
class RedirectPageController
{
    public function __construct(private readonly ContentUrlGenerator $urlGenerator)
    {
    }

    public function __invoke(PageModel $pageModel): Response
    {
        $status = 'temporary' === $pageModel->redirect ? Response::HTTP_SEE_OTHER : Response::HTTP_MOVED_PERMANENTLY;
        $url = $this->urlGenerator->generate($pageModel, [], UrlGeneratorInterface::ABSOLUTE_URL);

        return new RedirectResponse($url, $status);
    }
}
