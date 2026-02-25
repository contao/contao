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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\Input;
use Contao\PageModel;
use Contao\UnusedArgumentsException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[AsPage]
class RegularPageController extends AbstractPageController
{
    public function __invoke(PageModel $pageModel): Response
    {
        $this->container->get('contao.framework')->initialize();

        // Backup some globals (see #7659)
        $backup = [
            $GLOBALS['TL_HEAD'] ?? [],
            $GLOBALS['TL_BODY'] ?? [],
            $GLOBALS['TL_MOOTOOLS'] ?? [],
            $GLOBALS['TL_JQUERY'] ?? [],
            $GLOBALS['TL_USER_CSS'] ?? [],
            $GLOBALS['TL_FRAMEWORK_CSS'] ?? [],
            $this->container->get('contao.routing.response_context_accessor')->getResponseContext(),
        ];

        try {
            $response = $this->renderPage($pageModel);

            /** @var Input $input */
            $input = $this->container->get('contao.framework')->getAdapter(Input::class);

            if ($unused = $input->getUnusedRouteParameters()) {
                $input->setUnusedRouteParameters([]);

                throw new UnusedArgumentsException(\sprintf('Unused arguments: %s', implode(', ', $unused)));
            }

            return $response;
        } catch (UnusedArgumentsException $e) {
            // Restore the globals (see #7659)
            [
                $GLOBALS['TL_HEAD'],
                $GLOBALS['TL_BODY'],
                $GLOBALS['TL_MOOTOOLS'],
                $GLOBALS['TL_JQUERY'],
                $GLOBALS['TL_USER_CSS'],
                $GLOBALS['TL_FRAMEWORK_CSS'],
                $responseContext
            ] = $backup;

            $this->container->get('contao.routing.response_context_accessor')->setResponseContext($responseContext);

            throw $e;
        }
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['contao.framework'] = ContaoFramework::class;
        $services['contao.routing.response_context_accessor'] = ResponseContextAccessor::class;

        return $services;
    }
}
