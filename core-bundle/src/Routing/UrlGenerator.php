<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Routing;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
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
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var bool
     */
    private $prependLocale;

    /**
     * Constructor.
     *
     * @param UrlGeneratorInterface    $router
     * @param ContaoFrameworkInterface $framework
     * @param bool                     $prependLocale
     */
    public function __construct(UrlGeneratorInterface $router, ContaoFrameworkInterface $framework, $prependLocale)
    {
        $this->router = $router;
        $this->framework = $framework;
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
        $this->framework->initialize();

        if (!is_array($parameters)) {
            $parameters = [];
        }

        $context = $this->getContext();

        // Store the original request context
        $host = $context->getHost();
        $scheme = $context->getScheme();
        $httpPort = $context->getHttpPort();
        $httpsPort = $context->getHttpsPort();

        $this->prepareLocale($parameters);
        $this->prepareAlias($name, $parameters);
        $this->prepareDomain($context, $parameters, $referenceType);

        $url = $this->router->generate(
            'index' === $name ? 'contao_index' : 'contao_frontend',
            $parameters,
            $referenceType
        );

        // Reset the request context
        $context->setHost($host);
        $context->setScheme($scheme);
        $context->setHttpPort($httpPort);
        $context->setHttpsPort($httpsPort);

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
     * @throws MissingMandatoryParametersException
     */
    private function prepareAlias($alias, array &$parameters)
    {
        if ('index' === $alias) {
            return;
        }

        $hasAutoItem = false;
        $autoItems = $this->getAutoItems($parameters);

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        $parameters['alias'] = preg_replace_callback(
            '/\{([^\}]+)\}/',
            function ($matches) use ($alias, &$parameters, $autoItems, &$hasAutoItem, $config) {
                $param = $matches[1];

                if (!isset($parameters[$param])) {
                    throw new MissingMandatoryParametersException(
                        sprintf('Parameters "%s" is missing to generate a URL for "%s"', $param, $alias)
                    );
                }

                $value = $parameters[$param];
                unset($parameters[$param]);

                if (!$config->get('useAutoItem') || $hasAutoItem || !in_array($param, $autoItems, true)) {
                    return $param.'/'.$value;
                }

                $hasAutoItem = true;

                return $value;
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
        if (isset($parameters['_ssl'])) {
            $context->setScheme(true === $parameters['_ssl'] ? 'https' : 'http');
        }

        if (isset($parameters['_domain']) && '' !== $parameters['_domain']) {
            $this->addHostToContext($context, $parameters, $referenceType);
        }

        unset($parameters['_domain'], $parameters['_ssl']);
    }

    /**
     * Sets the context from the domain.
     *
     * @param RequestContext $context
     * @param array          $parameters
     * @param string         $referenceType
     */
    private function addHostToContext(RequestContext $context, array $parameters, &$referenceType)
    {
        list($host, $port) = $this->getHostAndPort($parameters['_domain']);

        if ($context->getHost() === $host) {
            return;
        }

        $context->setHost($host);
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL;

        if (!$port) {
            return;
        }

        if (isset($parameters['_ssl']) && true === $parameters['_ssl']) {
            $context->setHttpsPort($port);
        } else {
            $context->setHttpPort($port);
        }
    }

    /**
     * Extracts host and port from the domain.
     *
     * @param $domain
     *
     * @return array
     */
    private function getHostAndPort($domain)
    {
        if (false !== strpos($domain, ':')) {
            return explode(':', $domain, 2);
        }

        return [$domain, null];
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
