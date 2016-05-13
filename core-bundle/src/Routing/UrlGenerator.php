<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Routing;

use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * Generates Contao URLs.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class UrlGenerator implements UrlGeneratorInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var bool
     */
    private $prependLocale;

    /**
     * Constructor.
     *
     * @param UrlGeneratorInterface $router
     * @param bool                  $prependLocale
     */
    public function __construct(UrlGeneratorInterface $router, $prependLocale)
    {
        $this->router = $router;
        $this->prependLocale = $prependLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->router->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->router->getContext();
    }

    /**
     * Generates a Contao URL.
     *
     * @param string $name
     * @param array  $parameters
     * @param int    $referenceType
     *
     * @return string
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $route = 'index' === $name ? 'contao_index' : 'contao_frontend';

        if (!is_array($parameters)) {
            $parameters = [];
        }

        $context = $this->getContext();

        // Store the original request context
        $_host = $context->getHost();
        $_scheme = $context->getScheme();
        $_httpPort = $context->getHttpPort();
        $_httpsPort = $context->getHttpsPort();

        $this->prepareLocale($parameters);
        $this->prepareAlias($name, $parameters);
        $this->prepareDomain($context, $parameters, $referenceType);

        $url = $this->router->generate($route, $parameters, $referenceType);

        // Reset the request context
        $context->setHost($_host);
        $context->setScheme($_scheme);
        $context->setHttpPort($_httpPort);
        $context->setHttpsPort($_httpsPort);

        return $url;
    }

    /**
     * Removes the locale parameter if it is disabled.
     *
     * @param array $parameters
     */
    private function prepareLocale(array &$parameters)
    {
        if (!$this->prependLocale && array_key_exists('_locale', $parameters)) {
            unset($parameters['_locale']);
        }
    }

    /**
     * Adds the parameters to the alias.
     *
     * @param string $alias
     * @param array  $parameters
     *
     * @return array
     *
     * @throws MissingMandatoryParametersException
     */
    private function prepareAlias($alias, array &$parameters)
    {
        if ('index' === $alias) {
            return;
        }

        $hasAutoItem = false;
        $autoItems = $this->getAutoItems($parameters);

        $parameters['alias'] = preg_replace_callback(
            '/\{([^\}]+)\}/',
            function ($matches) use ($alias, &$parameters, $autoItems, &$hasAutoItem) {
                $param = $matches[1];

                if (!isset($parameters[$param])) {
                    throw new MissingMandatoryParametersException(
                        sprintf('Parameters "%s" is missing to generate a URL for "%s"', $param, $alias)
                    );
                }

                $value = $parameters[$param];
                unset($parameters[$param]);

                if (!$hasAutoItem && in_array($param, $autoItems, true)) {
                    $hasAutoItem = true;

                    return $value;
                }

                return $param.'/'.$value;
            },
            $alias
        );
    }

    /**
     * Forces the router to add the host if necessary.
     *
     * @param RequestContext $context
     * @param array          $parameters
     * @param int            $referenceType
     */
    private function prepareDomain(RequestContext $context, array &$parameters, &$referenceType)
    {
        if (!isset($parameters['_domain']) || '' === $parameters['_domain']) {
            unset($parameters['_domain'], $parameters['_ssl']);

            return;
        }

        list($host, $port) = explode(':', $parameters['_domain'], 2);

        if ($host !== $context->getHost()) {
            $context->setHost($host);
            $context->setHttpPort($port ?: 80);

            if (UrlGeneratorInterface::ABSOLUTE_URL !== $referenceType) {
                $referenceType = UrlGeneratorInterface::NETWORK_PATH;
            }

            if (isset($parameters['_domain']) && true === $parameters['_ssl']) {
                $context->setScheme('https');
                $context->setHttpsPort($port ?: 443);
                $referenceType = UrlGeneratorInterface::ABSOLUTE_URL;
            }
        }

        unset($parameters['_domain'], $parameters['_ssl']);
    }

    /**
     * Returns the auto_item key from the parameters or the global array.
     *
     * @param array $parameters
     *
     * @return array
     */
    private function getAutoItems(array $parameters)
    {
        if (isset($parameters['auto_item'])) {
            return [$parameters['auto_item']];
        }

        if (isset($GLOBALS['TL_AUTO_ITEM']) && is_array($GLOBALS['TL_AUTO_ITEM'])) {
            return $GLOBALS['TL_AUTO_ITEM'];
        }

        return [];
    }
}
