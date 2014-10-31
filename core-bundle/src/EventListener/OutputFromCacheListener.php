<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\CoreBundle\EventListener;

use Contao\Config;
use Contao\Controller;
use Contao\Environment;
use Contao\Input;
use Contao\Session;
use Contao\System;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Outputs a page from cache without loading controllers
 *
 * @author Leo Feyer <https://contao.org>
 * @author Andreas Schempp <http://terminal42.ch>
 */
class OutputFromCacheListener
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * Constructor
     *
     * @param Config $config The Contao configuration object
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Outputs the cached version of a page before the router handles anything
     *
     * @param GetResponseEvent $event The event object
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        // Build the page if a user is (potentially) logged in or there is POST data
        if (
            !empty($_POST)
            || Input::cookie('FE_USER_AUTH')
            || Input::cookie('FE_AUTO_LOGIN')
            || $_SESSION['DISABLE_CACHE']
            || isset($_SESSION['LOGIN_ERROR'])
            || $this->config->get('debugMode')
        ) {
            return;
        }

        // If the request string is empty, look for a cached page matching the
        // primary browser language. This is a compromise between not caching
        // empty requests at all and considering all browser languages, which
        // is not possible for various reasons.
        if ('' === Environment::get('request') || 'index.php' === Environment::get('request')) {

            // Return if the language is added to the URL and the empty domain will be redirected
            if ($this->config->get('addLanguageToUrl') && !$this->config->get('doNotRedirectEmpty')) {
                return;
            }

            $language = Environment::get('httpAcceptLanguage');
            $cacheKey = Environment::get('base') .'empty.'. $language[0];
        } else {
            $cacheKey = Environment::get('base') . Environment::get('request');
        }

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['getCacheKey']) && is_array($GLOBALS['TL_HOOKS']['getCacheKey'])) {
            foreach ($GLOBALS['TL_HOOKS']['getCacheKey'] as $callback) {
                $cacheKey = System::importStatic($callback[0])->$callback[1]($cacheKey);
            }
        }

        $found     = false;
        $cacheFile = null;

        // Check for a mobile layout
        if (
            'mobile' === Input::cookie('TL_VIEW')
            || (Environment::get('agent')->mobile && 'desktop' !== Input::cookie('TL_VIEW'))
        ) {
            $cacheKey  = md5($cacheKey . '.mobile');
            $cacheFile = TL_ROOT . '/system/cache/html/' . substr($cacheKey, 0, 1) . '/' . $cacheKey . '.html';

            if (file_exists($cacheFile)) {
                $found = true;
            }
        }

        // Check for a regular layout
        if (!$found) {
            $cacheKey  = md5($cacheKey);
            $cacheFile = TL_ROOT . '/system/cache/html/' . substr($cacheKey, 0, 1) . '/' . $cacheKey . '.html';

            if (file_exists($cacheFile)) {
                $found = true;
            }
        }

        // Return if the file does not exist
        if (!$found) {
            return;
        }

        $expire  = null;
        $content = null;
        $type    = null;

        // Include the file
        ob_start();
        require_once $cacheFile;

        // The file has expired
        if ($expire < time()) {
            ob_end_clean();
            return;
        }

        // Read the buffer
        $buffer = ob_get_clean();

        // Session required to determine the referer
        $session = Session::getInstance();
        $data    = $session->getData();

        // Set the new referer
        if (
            !isset($_GET['pdf'])
            && !isset($_GET['file'])
            && !isset($_GET['id'])
            && $data['referer']['current'] != Environment::get('requestUri')
        ) {
            $data['referer']['last']    = $data['referer']['current'];
            $data['referer']['current'] = substr(Environment::get('requestUri'), strlen(Environment::get('path')) + 1);
        }

        // Store the session data
        $session->setData($data);

        // Load the default language file (see #2644)
        System::loadLanguageFile('default');

        // Replace the insert tags and then re-replace the request_token
        // tag in case a form element has been loaded via insert tag
        $buffer = Controller::replaceInsertTags($buffer, false);
        $buffer = str_replace(['{{request_token}}', '[{]', '[}]'], [REQUEST_TOKEN, '{{', '}}'], $buffer);

        // Content type
        if (!$content) {
            $content = 'text/html';
        }

        $response = new Response($buffer);

        // Send the status header (see #6585)
        if ('error_403' === $type) {
            $response->setStatusCode(Response::HTTP_FORBIDDEN);
        } elseif ('error_404' === $type) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        $response->headers->set('Vary', 'User-Agent', false);
        $response->headers->set('Content-Type', $content . '; charset=' . $this->config->get('characterSet'));

        // Send the cache headers
        if (
            null !== $expire
            && ('both' === $this->config->get('cacheMode') || 'browser' === $this->config->get('cacheMode'))
        ) {
            $response->headers->set('Cache-Control', 'public, max-age=' . ($expire - time()));
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', $expire) . ' GMT');
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', time()) . ' GMT');
            $response->headers->set('Pragma', 'public');
        } else {
            $response->headers->set('Cache-Control', ['no-cache', 'pre-check=0, post-check=0']);
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
            $response->headers->set('Expires', 'Fri, 06 Jun 1975 15:10:00 GMT');
            $response->headers->set('Pragma', 'no-cache');
        }

        $event->setResponse($response);
    }
}
