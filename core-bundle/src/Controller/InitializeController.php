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

use Contao\CoreBundle\Response\InitializeControllerResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Custom controller to support legacy entry points.
 *
 * @deprecated Deprecated in Contao 4.0, to be removed in Contao 5.0
 */
class InitializeController extends AbstractController
{
    /**
     * Initializes the Contao framework.
     *
     * @throws \RuntimeException
     *
     * @Route("/_contao/initialize", name="contao_initialize")
     */
    public function indexAction(): InitializeControllerResponse
    {
        @trigger_error('Custom entry points are deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

        $masterRequest = $this->get('request_stack')->getMasterRequest();

        if (null === $masterRequest) {
            throw new \RuntimeException('The request stack did not contain a master request.');
        }

        $realRequest = Request::createFromGlobals();

        // Necessary to generate the correct base path
        foreach (['REQUEST_URI', 'SCRIPT_NAME', 'SCRIPT_FILENAME', 'PHP_SELF'] as $name) {
            $realRequest->server->set(
                $name,
                str_replace(TL_SCRIPT, 'index.php', $realRequest->server->get($name))
            );
        }

        $realRequest->attributes->replace($masterRequest->attributes->all());

        // Initialize the framework with the real request
        $this->get('request_stack')->push($realRequest);
        $this->get('contao.framework')->initialize();

        // Add the master request again. When Kernel::handle() is finished,
        // it will pop the current request, resulting in the real request being active.
        $this->get('request_stack')->push($masterRequest);

        return new InitializeControllerResponse('', 204);
    }
}
