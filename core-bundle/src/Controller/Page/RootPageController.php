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
use Contao\CoreBundle\ServiceAnnotation\Page;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Page(contentComposition=false)
 *
 * @internal
 */
class RootPageController extends AbstractController
{
    private ?LoggerInterface $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function __invoke(PageModel $pageModel): Response
    {
        $nextPage = $this->getNextPage((int) $pageModel->id);

        return $this->redirect($nextPage->getAbsoluteUrl());
    }

    private function getNextPage(int $rootPageId): PageModel
    {
        $nextPage = $this->getContaoAdapter(PageModel::class)->findFirstPublishedByPid($rootPageId);

        if ($nextPage instanceof PageModel) {
            return $nextPage;
        }

        if ($this->logger) {
            $this->logger->error('No active page found under root page "'.$rootPageId.'"');
        }

        throw new NoActivePageFoundException('No active page found under root page.');
    }
}
