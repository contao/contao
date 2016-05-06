<?php

namespace Contao\CoreBundle\Routing;

use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * UrlGenerator
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
     * @param UrlMatcherInterface $router
     * @param bool                $prependLocale
     */
    public function __construct(UrlGeneratorInterface $router, $prependLocale)
    {
        $this->router = $router;
        $this->prependLocale = $prependLocale;
    }

    /**
     * @inheritdoc
     */
    public function setContext(RequestContext $context)
    {
        $this->router->setContext($context);
    }

    /**
     * @inheritdoc
     */
    public function getContext()
    {
        return $this->router->getContext();
    }

    /**
     * @todo write explanation
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
        $parameters = is_array($parameters) ? $parameters : [];

        if (!$this->prependLocale && array_key_exists('_locale', $parameters)) {
            unset($parameters['_locale']);
        }

        $parameters = $this->prepareParameters($name, $parameters);

        return $this->router->generate($route, $parameters, $referenceType);
    }

    /**
     * @param string $alias
     * @param array  $parameters
     *
     * @return array
     *
     * @throws MissingMandatoryParametersException
     */
    private function prepareParameters($alias, array $parameters)
    {
        $hasAutoItem = false;
        $autoItem = $this->getAutoItems($parameters);

        $parameters['alias'] = preg_replace_callback(
            '/\{([^\}]+)\}/',
            function ($matches) use ($alias, &$parameters, $autoItem, &$hasAutoItem) {
                $param = $matches[1];

                if (!isset($parameters[$param])) {
                    throw new MissingMandatoryParametersException(
                        sprintf('Parameters "%s" is missing to generate a URL for "%s"', $param, $alias)
                    );
                }

                $value = $parameters[$param];
                unset($parameters[$param]);

                if (!$hasAutoItem && in_array($param, $autoItem, true)) {
                    $hasAutoItem = true;

                    return $value;
                }

                return $param . '/' . $value;
            },
            $alias
        );

        return $parameters;
    }

    /**
     * @param array $parameters
     *
     * @return array
     */
    private function getAutoItems(array $parameters)
    {
        if (array_key_exists('auto_item', $parameters)) {
            return [$parameters['auto_item']];
        }

        if ((isset($GLOBALS['TL_AUTO_ITEM']) && is_array($GLOBALS['TL_AUTO_ITEM']))) {
            return $GLOBALS['TL_AUTO_ITEM'];
        }

        return [];
    }
}
