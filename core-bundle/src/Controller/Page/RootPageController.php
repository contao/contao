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
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Response;

class RootPageController extends AbstractController
{
    public function __invoke(PageModel $pageModel): Response
    {
        if ('root' !== $pageModel->type) {
            throw new \InvalidArgumentException('Invalid page type');
        }

        return $this->redirectToContent($this->getNextPage((int) $pageModel->id));
    }

    private function getNextPage(int $rootPageId): PageModel
    {
        $this->initializeContaoFramework();

        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->get('contao.framework')->getAdapter(PageModel::class);
        $nextPage = $pageAdapter->findFirstPublishedByPid($rootPageId);

        if (null !== $nextPage) {
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
