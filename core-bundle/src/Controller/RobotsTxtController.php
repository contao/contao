<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\RobotsTxtEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\PageModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use webignition\RobotsTxt\File\Parser;

/**
 * @Route(defaults={"_scope" = "frontend"})
 */
class RobotsTxtController
{
    /**
     * @var ContaoFramework
     */
    private $contaoFramework;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(ContaoFramework $contaoFramework, EventDispatcherInterface $eventDispatcher)
    {
        $this->contaoFramework = $contaoFramework;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Route("/robots.txt", name="contao_robots_txt")
     */
    public function __invoke(Request $request): Response
    {
        $this->contaoFramework->initialize();

        $rootPage = $this->contaoFramework->getAdapter(PageModel::class)
            ->findPublishedFallbackByHostname($request->server->get('HTTP_HOST'), [], true)
        ;

        if (null === $rootPage) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $parser = new Parser();
        $parser->setSource($rootPage->robotsTxt);
        $file = $parser->getFile();

        $this->eventDispatcher->dispatch(new RobotsTxtEvent($file, $request, $rootPage), ContaoCoreEvents::ROBOTS_TXT);

        return new Response((string) $file, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
