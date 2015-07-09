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
use Contao\Encryption;
use Contao\InstallationBundle\InstallTool;
use Contao\InstallationBundle\InstallToolUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Translator;

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
     * @var InstallToolUser
     */
    private $user;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * Constructor.
     *
     * @param \Twig_Environment $twig         The Twig environment
     * @param RequestStack      $requestStack The request stack
     * @param InstallToolUser   $user         The user object
     * @param Translator        $translator   The translator
     * @param string            $rootDir      The root directory
     */
    public function __construct(\Twig_Environment $twig, RequestStack $requestStack, InstallToolUser $user, Translator $translator, $rootDir)
    {
        $this->twig         = $twig;
        $this->requestStack = $requestStack;
        $this->user         = $user;
        $this->translator   = $translator;
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

        if ($installTool->shouldAcceptLicense()) {
            return $this->acceptLicense();
        }

        $installTool->createLocalConfigurationFiles();

        if ('' === Config::get('installPassword')) {
            return $this->setPassword();
        }

        if (!$this->user->isAuthenticated()) {
            return $this->login();
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

        $this->persistContaoParameter('licenseAccepted', true);

        return $this->getRedirectResponse();
    }

    /**
     * Sets the install tool password.
     *
     * @return Response The response object
     */
    private function setPassword()
    {
        $request = $this->requestStack->getCurrentRequest();

        if ('tl_password' !== $request->request->get('FORM_SUBMIT')) {
            return $this->render('password.html.twig');
        }

        $password     = $request->request->get('password');
        $confirmation = $request->request->get('confirmation');

        // The passwords do not match
        if ($password !== $confirmation) {
            return $this->render('password.html.twig', [
                'error' => $this->translator->trans('password_confirmation_mismatch'),
            ]);
        }

        // The passwords is too short
        if (strlen(utf8_decode($password)) < Config::get('minPasswordLength')) {
            return $this->render('password.html.twig', [
                'error' => sprintf($this->translator->trans('password_too_short'), Config::get('minPasswordLength')),
            ]);
        }

        $this->persistContaoParameter('installPassword', Encryption::hash($password));
        $this->user->setAuthenticated(true);

        return $this->getRedirectResponse();
    }

    /**
     * Logs in the install tool user.
     *
     * @return RedirectResponse|Response The response object
     */
    private function login()
    {
        $request = $this->requestStack->getCurrentRequest();

        if ('tl_login' !== $request->request->get('FORM_SUBMIT')) {
            return $this->render('login.html.twig');
        }

        if (!Encryption::verify($request->request->get('password'), Config::get('installPassword'))) {
            $this->persistContaoParameter('installCount', Config::get('installCount') + 1);

            return $this->render('login.html.twig', [
                'error' => $this->translator->trans('invalid_password'),
            ]);
        }

        $this->persistContaoParameter('installCount', 0);
        $this->user->setAuthenticated(true);

        return $this->getRedirectResponse();
    }

    /**
     * Returns a redirect response to reload the page.
     *
     * @return RedirectResponse The redirect response
     */
    private function getRedirectResponse()
    {
        return new RedirectResponse($this->requestStack->getCurrentRequest()->getRequestUri());
    }

    /**
     * Persists a Contao configuration parameters.
     *
     * @param string $key   The key
     * @param mixed  $value The value
     */
    private function persistContaoParameter($key, $value)
    {
        $config = Config::getInstance();
        $config->persist($key, $value);
        $config->save();
    }
}
