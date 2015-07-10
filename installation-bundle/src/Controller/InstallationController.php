<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Controller;

use Contao\Encryption;
use Contao\InstallationBundle\Config\ParameterDumper;
use Contao\InstallationBundle\Database\ConnectionFactory;
use Contao\InstallationBundle\InstallTool;
use Contao\InstallationBundle\InstallToolUser;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles the installation process.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InstallationController extends ContainerAware
{
    /**
     * @var InstallToolUser
     */
    private $user;

    /**
     * @var InstallTool
     */
    private $installTool;

    /**
     * @var array
     */
    private $context = [];

    /**
     * Constructor.
     *
     * @param InstallToolUser $user        The user object
     * @param InstallTool     $installTool The install tool object
     */
    public function __construct(InstallToolUser $user, InstallTool $installTool) {
        $this->user        = $user;
        $this->installTool = $installTool;
    }

    /**
     * Handles the installation process.
     *
     * @return Response The response object
     */
    public function indexAction()
    {
        if ($this->installTool->isLocked()) {
            return $this->render('locked.html.twig');
        }

        if (!$this->installTool->canWriteFiles()) {
            return $this->render('not_writable.html.twig');
        }

        if ($this->installTool->shouldAcceptLicense()) {
            return $this->acceptLicense();
        }

        $this->installTool->createLocalConfigurationFiles();

        if ('' === $this->installTool->getConfig('installPassword')) {
            return $this->setPassword();
        }

        if (!$this->user->isAuthenticated()) {
            return $this->login();
        }

        if (!$this->installTool->canConnectToDatabase($this->container->getParameter('database_name'))) {
            return $this->setUpDatabaseConnection();
        }

        if (null !== ($response = $this->runDatabaseUpdates())) {
            return $response;
        }

        // adjustDatabaseTables()
        // importExampleWebsite()
        // createAdminUser()

        return $this->render('main.html.twig', $this->context);
    }

    /**
     * Renders a form to accept the license.
     *
     * @return Response|RedirectResponse The response object
     */
    private function acceptLicense()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ('tl_license' !== $request->request->get('FORM_SUBMIT')) {
            return $this->render('license.html.twig');
        }

        $this->installTool->persistConfig('licenseAccepted', true);

        return $this->getRedirectResponse();
    }

    /**
     * Sets the install tool password.
     *
     * @return Response The response object
     */
    private function setPassword()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ('tl_password' !== $request->request->get('FORM_SUBMIT')) {
            return $this->render('password.html.twig');
        }

        $password     = $request->request->get('password');
        $confirmation = $request->request->get('confirmation');

        // The passwords do not match
        if ($password !== $confirmation) {
            return $this->render('password.html.twig', [
                'error' => $this->trans('password_confirmation_mismatch'),
            ]);
        }

        $minlength = $this->installTool->getConfig('minPasswordLength');

        // The passwords is too short
        if (strlen(utf8_decode($password)) < $minlength) {
            return $this->render('password.html.twig', [
                'error' => sprintf($this->trans('password_too_short'), $minlength),
            ]);
        }

        $this->installTool->persistConfig('installPassword', Encryption::hash($password));
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
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ('tl_login' !== $request->request->get('FORM_SUBMIT')) {
            return $this->render('login.html.twig');
        }

        $verified = Encryption::verify(
            $request->request->get('password'),
            $this->installTool->getConfig('installPassword')
        );

        if (!$verified) {
            $this->installTool->increaseLoginCount();

            return $this->render('login.html.twig', [
                'error' => $this->trans('invalid_password'),
            ]);
        }

        $this->installTool->resetLoginCount();
        $this->user->setAuthenticated(true);

        return $this->getRedirectResponse();
    }

    /**
     * Sets up the database connection.
     *
     * @return Response|null The response object
     */
    private function setUpDatabaseConnection()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        $parameters['parameters'] = [
            'database_host'     => $this->container->getParameter('database_host'),
            'database_port'     => $this->container->getParameter('database_port'),
            'database_name'     => $this->container->getParameter('database_name'),
            'database_user'     => $this->container->getParameter('database_user'),
            'database_password' => $this->container->getParameter('database_password'),
        ];

        if ('tl_database_login' !== $request->request->get('FORM_SUBMIT')) {
            return $this->render('database.html.twig', $parameters);
        }

        $parameters['parameters'] = [
            'database_host'     => $request->request->get('dbHost'),
            'database_port'     => $request->request->get('dbPort'),
            'database_name'     => $request->request->get('dbName'),
            'database_user'     => $request->request->get('dbUser'),
            'database_password' => $this->container->getParameter('database_password'),
        ];

        if ('*****' !== $request->request->get('dbPassword')) {
            $parameters['parameters']['database_password'] = $request->request->get('dbPassword');
        }

        $this->installTool->setConnection(ConnectionFactory::create($parameters));

        if (!$this->installTool->canConnectToDatabase($parameters['parameters']['database_name'])) {
            return $this->render('database.html.twig', array_merge(
                $parameters,
                ['database_error' => $this->trans('database_could_not_connect')]
            ));
        }

        $dumper = new ParameterDumper($this->container->getParameter('kernel.root_dir'));
        $dumper->setParameters($parameters);
        $dumper->dump();

        return $this->getRedirectResponse();
    }

    /**
     * Runs the database updates.
     *
     * @return Response|null
     */
    private function runDatabaseUpdates()
    {
        if ($this->installTool->isFreshInstallation()) {
            return null;
        }
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
        return new Response($this->container->get('twig')->render($name, $context));
    }

    /**
     * Translate a key.
     *
     * @param string $key The translation key
     *
     * @return string The translated string
     */
    private function trans($key)
    {
        return $this->container->get('translator.default')->trans($key);
    }

    /**
     * Returns a redirect response to reload the page.
     *
     * @return RedirectResponse The redirect response
     */
    private function getRedirectResponse()
    {
        return new RedirectResponse($this->container->get('request_stack')->getCurrentRequest()->getRequestUri());
    }
}
