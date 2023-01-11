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
use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\ContentCompositionInterface;
use Contao\FrontendIndex;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[AsPage('error_401', path: false)]
#[AsPage('error_403', path: false)]
#[AsPage('error_404', path: false)]
#[AsPage('error_503', path: false)]
class ErrorPageController extends AbstractController implements ContentCompositionInterface
{
    public function __construct(private ContaoFramework $framework)
    {
    }

    public function __invoke(PageModel $pageModel): Response
    {
        $this->framework->initialize();

        return $this->framework
            ->createInstance(FrontendIndex::class)
            ->renderPage($pageModel)
        ;
    }

    public function supportsContentComposition(PageModel $pageModel): bool
    {
        return 'error_503' === $pageModel->type || !$pageModel->autoforward;
    }
}
