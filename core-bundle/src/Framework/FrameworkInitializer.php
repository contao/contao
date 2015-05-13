<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */


namespace Contao\CoreBundle\Framework;

use Contao\ClassLoader;
use Contao\CoreBundle\Adapter\ConfigAdapter;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Exception\AjaxRedirectResponseException;
use Contao\CoreBundle\Exception\IncompleteInstallationException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Session\Attribute\AttributeBagAdapter;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Contao\Input;
use Contao\System;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Service for Initialization-Process of Legacy-Framework
 *
 * @author Dominik Tomasi <https://github.com/dtomasi>
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class FrameworkInitializer
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $tokenManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var ConfigAdapter
     */
    private $config;

    /**
     * @var string
     */
    private $csrfTokenName;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @inheritdoc
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container     = $container;
        $this->router        = $this->container->get('router');
        $this->session       = $this->container->get('session');
        $this->rootDir       = dirname($this->container->getParameter('kernel.root_dir'));
        $this->tokenManager  = $this->container->get('security.csrf.token_manager');
        $this->csrfTokenName = $this->container->getParameter('contao.csrf_token_name');
        $this->config        = $this->container->get('contao.adapter.config');
    }

    /**
     * Check Framework is initialized
     *
     * @return bool
     */
    public function isInitialized()
    {
        return $this->initialized;
    }

    /**
     * Start Initialization-Process
     */
    public function initialize()
    {
        if ($this->isInitialized()) {
            return;
        }

        $this->initialized = true;

        $this->setConstants();
        $this->setErrorReporting();
        $this->includeHelpers();
        $this->setErrorLog();
        $this->includeBasicClasses();
        $this->preloadConfig();
        $this->scanAndRegisterClassLoader();
        $this->initializeLegacySessionAccess();
        $this->setDefaultLanguage();

        // Invoke Legacy-HOOKS
        if (isset($GLOBALS['TL_HOOKS']['initializeSystem']) && is_array($GLOBALS['TL_HOOKS']['initializeSystem'])) {
            foreach ($GLOBALS['TL_HOOKS']['initializeSystem'] as $callback) {
                System::importStatic($callback[0])->$callback[1]();
            }
        }

        // Included initconfig.php
        if (file_exists($this->rootDir . '/system/config/initconfig.php')) {
            include $this->rootDir . '/system/config/initconfig.php';
        }

        $this->initializeConfig();
        $this->validateInstallation();

        Input::initialize();

        $this->setTimezone();
        $this->setMbStingEncoding();
        $this->handleRequestToken();

    }

    /**
     * Defines Constants required for Contao-Framework
     */
    private function setConstants()
    {
        if ($this->container->isScopeActive(ContaoCoreBundle::SCOPE_BACKEND)) {
            define('TL_MODE', 'BE');
        } else {
            define('TL_MODE', 'FE');
        }

        define('TL_START', microtime(true));
        define('TL_ROOT', $this->rootDir);

        $request = $this->container->get('request_stack')->getCurrentRequest();

        if (null !== $request) {
            define('TL_REFERER_ID', $request->attributes->get('_contao_referer_id', ''));
        }

        if (null !== $request) {
            $route = $this->router->generate(
                $request->attributes->get('_route'),
                $request->attributes->get('_route_params')
            );

            $route = substr($route, strlen($request->getBasePath()) + 1);

        } else {
            $route = 'console';
        }

        define('TL_SCRIPT', $route);

        if ($this->container->isScopeActive(ContaoCoreBundle::SCOPE_BACKEND)) {
            define('BE_USER_LOGGED_IN', false);
            define('FE_USER_LOGGED_IN', false);
        }

        if (null !== $request) {
            define('TL_PATH', $request->getBasePath());
        }
    }

    /**
     * Sets error_reporting
     */
    private function setErrorReporting()
    {
        error_reporting($this->container->getParameter('contao.error_level'));
    }

    /**
     * Includes some helper files
     */
    private function includeHelpers()
    {
        require __DIR__ . '/../Resources/contao/helper/functions.php';
        require __DIR__ . '/../Resources/contao/config/constants.php';
        require __DIR__ . '/../Resources/contao/helper/interface.php';
        require __DIR__ . '/../Resources/contao/helper/exception.php';
    }

    /**
     * Sets error_log
     */
    private function setErrorLog()
    {
        // TODO: use Monolog to log errors
        if (function_exists('ini_set')) {
            ini_set('error_log', $this->rootDir . '/system/logs/error.log');
        }
    }

    /**
     * Includes basic Classes for Contao-Framework
     */
    private function includeBasicClasses()
    {
        $basicClasses = [
            'Config',
            'ClassLoader',
            'TemplateLoader',
            'ModuleLoader',
        ];

        foreach ($basicClasses as $class) {
            if (!class_exists($class, false)) {
                require_once __DIR__ . '/../../src/Resources/contao/library/Contao/' . $class . '.php';
                class_alias('Contao\\' . $class, $class);
            }
        }
    }

    /**
     * Preloads \Contao\Config via ConfigAdapter
     */
    private function preloadConfig()
    {
        $this->config->preload();
    }

    /**
     * Scan and Register Classes in Contao´s ClassLoader
     */
    private function scanAndRegisterClassLoader()
    {
        ClassLoader::scanAndRegister();
    }

    /**
     * Initializes Session access for BC
     */
    private function initializeLegacySessionAccess()
    {
        $_SESSION['BE_DATA'] = $this->session->getBag('contao_backend');
        $_SESSION['FE_DATA'] = $this->session->getBag('contao_frontend');
    }

    /**
     * Sets the Default Language
     */
    private function setDefaultLanguage()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        $language = 'en';

        if (null !== $request) {
            $language = str_replace('_', '-', $request->getLocale());
        }

        // Backwards compatibility
        $GLOBALS['TL_LANGUAGE']  = $language;
        $_SESSION['TL_LANGUAGE'] = $language;
    }

    /**
     * Initializes \Contao\Config via ConfigAdapter
     */
    private function initializeConfig()
    {
        $this->config->initialize();
    }

    /**
     * Validates the Installation is complete
     */
    private function validateInstallation()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if (null === $request || 'contao_backend_install' === $request->attributes->get('_route')) {
            return;
        }

        // Show the "incomplete installation" message
        if (!$this->config->isComplete()) {
            throw new IncompleteInstallationException(
                'The installation has not been completed. Open the Contao install tool to continue.'
            );
        }
    }

    /**
     * Sets the Timezone
     */
    private function setTimezone()
    {
        if (function_exists('ini_set')) {
            ini_set('date.timezone', $this->config->get('timeZone'));
        }
        date_default_timezone_set($this->config->get('timeZone'));
    }

    /**
     * Sets MBString encoding
     */
    private function setMbStingEncoding()
    {
        // Set the mbstring encoding
        if (USE_MBSTRING && function_exists('mb_regex_encoding')) {
            mb_regex_encoding($this->config->get('characterSet'));
        }
    }

    /**
     * Handles RequestToken
     */
    private function handleRequestToken()
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();

        // Backwards compatibility
        if (!defined('REQUEST_TOKEN')) {
            define('REQUEST_TOKEN', $this->tokenManager->getToken($this->csrfTokenName)->getValue());
        }

        if (null === $request || 'POST' !== $request->getRealMethod()) {
            return;
        }

        $token = new CsrfToken($this->csrfTokenName, Input::post('REQUEST_TOKEN'));

        if ($this->tokenManager->isTokenValid($token)) {
            return;
        }

        if ($request->isXmlHttpRequest()) {
            throw new AjaxRedirectResponseException($this->router->generate('contao_backend'));
        }

        // FIXME: This forces all routes handling POST data to pass a REQUEST_TOKEN
        throw new InvalidRequestTokenException('Invalid request token. Please reload the page and try again.');
    }
}
