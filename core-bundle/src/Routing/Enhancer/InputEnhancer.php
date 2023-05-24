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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Input;
use Contao\PageModel;
use Symfony\Cmf\Component\Routing\Enhancer\RouteEnhancerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class InputEnhancer implements RouteEnhancerInterface
{
    private ContaoFramework $framework;

    /**
     * @internal
     */
    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function enhance(array $defaults, Request $request): array
    {
        $page = $defaults['pageModel'] ?? null;

        if (!$page instanceof PageModel) {
            return $defaults;
        }

        $this->framework->initialize(true);

        $input = $this->framework->getAdapter(Input::class);

        if (!empty($page->urlPrefix)) {
            $input->setGet('language', LocaleUtil::formatAsLanguageTag($page->rootLanguage));
        }

        if (empty($defaults['parameters'])) {
            return $defaults;
        }

        $config = $this->framework->getAdapter(Config::class);
        $fragments = explode('/', substr($defaults['parameters'], 1));
        $inputKeys = [];

        // Add the second fragment as auto_item if the number of fragments is even
        if (0 !== \count($fragments) % 2) {
            if (!$config->get('useAutoItem')) {
                throw new ResourceNotFoundException('Invalid number of arguments');
            }

            array_unshift($fragments, 'auto_item');
        }

        for ($i = 0, $c = \count($fragments); $i < $c; $i += 2) {
            // Skip key value pairs if the key is empty (see #4702)
            if ('' === $fragments[$i]) {
                throw new ResourceNotFoundException('Empty fragment key in path');
            }

            // Abort if there is a duplicate parameter (duplicate content) (see #4277)
            if ($request->query->has($fragments[$i]) || \in_array($fragments[$i], $inputKeys, true)) {
                throw new ResourceNotFoundException(sprintf('Duplicate parameter "%s" in path', $fragments[$i]));
            }

            // Abort if the request contains an auto_item keyword (duplicate content) (see #4012)
            if (
                isset($GLOBALS['TL_AUTO_ITEM'])
                && $config->get('useAutoItem')
                && \in_array($fragments[$i], $GLOBALS['TL_AUTO_ITEM'], true)
            ) {
                throw new ResourceNotFoundException(sprintf('"%s" is an auto_item keyword (duplicate content)', $fragments[$i]));
            }

            $inputKeys[] = $fragments[$i];
            $input->setGet($fragments[$i], $fragments[$i + 1], true);
        }

        return $defaults;
    }
}
