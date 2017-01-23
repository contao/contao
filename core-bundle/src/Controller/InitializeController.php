<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Response\InitializeControllerResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Custom controller to support legacy entry point scripts.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 *
 * @deprecated Deprecated in Contao 4.0, to be removed in Contao 5.0
 */
class InitializeController extends Controller
{
    /**
     * Initializes the Contao framework.
     *
     * @return InitializeControllerResponse
     *
     * @Route("/_contao/initialize", name="contao_initialize")
     */
    public function indexAction()
    {
        @trigger_error('Custom entry points are deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);

        $masterRequest = $this->get('request_stack')->getMasterRequest();
        $realRequest = Request::createFromGlobals();
        $scope = ContaoCoreBundle::SCOPE_FRONTEND;

        if (defined('TL_MODE') && 'BE' === TL_MODE) {
            $scope = ContaoCoreBundle::SCOPE_BACKEND;
        }

        // Necessary to generate the correct base path
        foreach (['REQUEST_URI', 'SCRIPT_NAME', 'SCRIPT_FILENAME', 'PHP_SELF'] as $name) {
            $realRequest->server->set(
                $name,
                str_replace(TL_SCRIPT, 'app.php', $realRequest->server->get($name))
            );
        }

        $realRequest->attributes->replace($masterRequest->attributes->all());

        // Initialize the framework with the real request
        $this->get('request_stack')->push($realRequest);

        if (method_exists('Symfony\Component\DependencyInjection\Container', 'enterScope')) {
            $this->container->enterScope($scope);
        }

        $this->container->get('contao.framework')->initialize();

        // Add the master request again. When Kernel::handle() is finished,
        // it will pop the current request, resulting in the real request being active.
        $this->get('request_stack')->push($masterRequest);

        return new InitializeControllerResponse('', 204);
    }
}
