<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\ContaoCoreBundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the Contao initialize.php route.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class InitializeController extends Controller
{
    /**
     * Runs the initialize action for legacy entry point scripts.
     *
     * @return Response
     *
     * @Route("/_initialize", name="contao_initialize")
     */
    public function indexAction()
    {
        trigger_error('Custom entry points are deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

        $masterRequest = $this->get('request_stack')->getMasterRequest();
        $realRequest   = Request::createFromGlobals();
        $scope         = ContaoCoreBundle::SCOPE_FRONTEND;

        if (defined('TL_MODE') && TL_MODE === 'BE') {
            $scope = ContaoCoreBundle::SCOPE_BACKEND;
        }

        // Necessary to make the base path correct
        foreach (['REQUEST_URI', 'SCRIPT_NAME', 'SCRIPT_FILENAME', 'PHP_SELF'] as $name) {
            $realRequest->server->set(
                $name,
                str_replace(TL_SCRIPT, 'app.php', $realRequest->server->get($name))
            );
        }

        // Boot the framework with the real request
        $this->get('request_stack')->push($realRequest);
        $this->container->enterScope($scope);
        $this->container->get('contao.framework')->initialize();

        // Add the master request again. When Kernel::handle() is finished,
        // it will pop the current request, resulting in the real request being active.
        $this->get('request_stack')->push($masterRequest);

        return new Response();
    }
}
