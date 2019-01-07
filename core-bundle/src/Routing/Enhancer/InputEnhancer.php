<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Enhancer;

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Input;
use Contao\PageModel;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class InputEnhancer implements RouteEnhancerInterface
{
    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var Input|Adapter
     */
    private $inputAdapter;

    /**
     * @var Config|Adapter
     */
    private $configAdapter;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
        $this->inputAdapter = $framework->getAdapter(Input::class);
        $this->configAdapter = $framework->getAdapter(Config::class);
    }

    /**
     * {@inheritdoc}
     */
    public function enhance(array $defaults, Request $request): array
    {
        if (!isset($defaults['pageModel']) || !$defaults['pageModel'] instanceof PageModel) {
            return $defaults;
        }

        $this->framework->initialize();

        if (!empty($defaults['_locale']) && $this->configAdapter->get('addLanguageToUrl')) {
            $this->inputAdapter->setGet('language', $defaults['_locale']);
        }

        if (empty($defaults['parameters'])) {
            return $defaults;
        }

        $fragments = explode('/', substr($defaults['parameters'], 1));

        // Add the second fragment as auto_item if the number of fragments is even
        if ($this->configAdapter->get('useAutoItem') && 0 !== \count($fragments) % 2) {
            array_unshift($fragments, 'auto_item');
        }

        for ($i = 0, $c = \count($fragments); $i < $c; $i += 2) {
            // Skip key value pairs if the key is empty (see #4702)
            if ('' === $fragments[$i]) {
                continue;
            }

            // Abort if there is a duplicate parameter (duplicate content) (see #4277)
            // Do not use the request here, as we only need to make sure not to overwrite globals with Input::setGet()
            if (isset($_GET[$fragments[$i]])) {
                throw new ResourceNotFoundException(sprintf('Duplicate parameter "%s" in path', $fragments[$i]));
            }

            // Abort if the request contains an auto_item keyword (duplicate content) (see #4012)
            if (
                isset($GLOBALS['TL_AUTO_ITEM'])
                && $this->configAdapter->get('useAutoItem')
                && \in_array($fragments[$i], $GLOBALS['TL_AUTO_ITEM'], true)
            ) {
                throw new ResourceNotFoundException(
                    sprintf('"%s" is an auto_item keyword (duplicate content)', $fragments[$i])
                );
            }

            $this->inputAdapter->setGet(urldecode($fragments[$i]), urldecode($fragments[$i + 1] ?? ''), true);
        }

        return $defaults;
    }
}
