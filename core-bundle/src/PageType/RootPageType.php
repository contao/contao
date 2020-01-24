<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\PageType;

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\PageModel;
use Contao\PageRoot;
use Symfony\Component\Routing\Route;

class RootPageType extends AbstractSinglePageType implements HasLegacyPageInterface
{
    protected static $features = [];

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    public function __construct(ContaoFrameworkInterface $framework)
    {
        $this->framework = $framework;
    }

    public function getLegacyPageClass(): string
    {
        return PageRoot::class;
    }

    public function getRoutes(PageModel $pageModel, bool $prependLocale, string $urlSuffix): iterable
    {
        if ('root' !== $pageModel->type && 'index' !== $pageModel->alias && '/' !== $pageModel->alias) {
            return [];
        }

        $path = '/';
        $requirements = [];
        $defaults = $this->getRouteDefaults($pageModel);

        if ($prependLocale) {
            $path = '/{_locale}'.$path;
            $requirements['_locale'] = $pageModel->rootLanguage;
        }

        yield 'tl_page.'.$pageModel->id.'.root' => new Route(
            $path,
            $defaults,
            $requirements,
            [],
            $pageModel->domain,
            $pageModel->rootUseSSL ? 'https' : null,
            []
        );

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        if (!$config->get('doNotRedirectEmpty')) {
            $defaults['_controller'] = 'Symfony\Bundle\FrameworkBundle\Controller\RedirectController::urlRedirectAction';
            $defaults['path'] = '/'.$pageModel->language.'/';
            $defaults['permanent'] = true;
        }

        yield 'tl_page.'.$pageModel->id.'.fallback' => new Route(
            '/',
            $defaults,
            [],
            [],
            $pageModel->domain,
            $pageModel->rootUseSSL ? 'https' : null,
            []
        );
    }
}
