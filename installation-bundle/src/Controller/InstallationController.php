<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Controller;

use Contao\Config;
use Contao\InstallationBundle\InstallTool;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the installation process.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InstallationController
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * Constructor.
     *
     * @param \Twig_Environment $twig         The Twig environment
     * @param RequestStack      $requestStack The request stack
     * @param string            $rootDir      The root directory
     */
    public function __construct(\Twig_Environment $twig, RequestStack $requestStack, $rootDir)
    {
        $this->twig         = $twig;
        $this->requestStack = $requestStack;
        $this->rootDir      = $rootDir;
    }

    /**
     * Handles the installation process.
     * 
     * @return Response The response object
     */
    public function indexAction()
    {
        $installTool = new InstallTool($this->rootDir);

        if ($installTool->isLocked()) {
            return $this->render('locked.html.twig');
        }

        if (!$installTool->canWriteFiles()) {
            return $this->render('not_writable.html.twig');
        }

        $installTool->createLocalConfigurationFiles();

        if ($installTool->shouldAcceptLicense()) {
            return $this->acceptLicense();
        }

        return $this->render('layout.html.twig');
    }

    /**
     * Renders a template.
     *
     * @param string $name    The template name
     * @param array  $context The context array
     *
     * @return Response The response object
     */
    private function render($name, $context = [])
    {
        return new Response($this->twig->render($name, $context));
    }

    /**
     * Renders a form to accept the license.
     *
     * @return Response|RedirectResponse The response object
     */
    private function acceptLicense()
    {
        $request = $this->requestStack->getCurrentRequest();

        if ('tl_license' !== $request->request->get('FORM_SUBMIT')) {
            return $this->render('license.html.twig');
        }

        $config = Config::getInstance();
        $config->persist('licenseAccepted', true);
        $config->save();

        return new RedirectResponse($request->getRequestUri());
    }
}
