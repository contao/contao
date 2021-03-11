<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller\Backend;

use Contao\Ajax;
use Contao\ArticleModel;
use Contao\Backend;
use Contao\BackendTemplate;
use Contao\BackendUser;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Backend\BackendState;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Controller\BackendModule\BackendModuleController;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Fragment\FragmentHandler;
use Contao\CoreBundle\Fragment\FragmentRegistryInterface;
use Contao\CoreBundle\Fragment\Reference\BackendModuleReference;
use Contao\CoreBundle\Fragment\Reference\DashboardWidgetReference;
use Contao\CoreBundle\Fragment\Reference\FragmentReference;
use Contao\CoreBundle\Picker\PickerBuilderInterface;
use Contao\CoreBundle\Util\PackageUtil;
use Contao\Environment;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Knp\Menu\Renderer\RendererInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("", name="contao_backend")
 */
class BackendMainController extends AbstractController
{
    private $fragmentRegistry;
    private $fragmentHandler;
    private $menuRenderer;

    public function __construct(FragmentRegistryInterface $fragmentRegistry, FragmentHandler $fragmentHandler, RendererInterface $menuRenderer)
    {
        $this->fragmentRegistry = $fragmentRegistry;
        $this->fragmentHandler = $fragmentHandler;
        $this->menuRenderer = $menuRenderer;
    }

    public function __invoke(Request $request): Response
    {
        $this->initializeContaoFramework();

        $user = $this->get('contao.framework')->createInstance(BackendUser::class);

        // Ported Backend::__construct()
        Controller::setStaticUrls();

        System::loadLanguageFile('default');
        System::loadLanguageFile('modules');

        if (!$this->get('security.authorization_checker')->isGranted('ROLE_USER')) {
            throw new AccessDeniedException('Access denied');
        }

        // Password change required
        if ($user->pwChange && !$this->get('security.authorization_checker')->isGranted('ROLE_PREVIOUS_ADMIN')) {
            return $this->redirect('contao/password.php');
        }

        // Two-factor setup required
        if (!$user->useTwoFactor && $this->getParameter('contao.security.two_factor.enforce_backend') && 'security' !== $request->query->get('do')) {
            return $this->redirect($this->generateUrl('contao_backend', ['do' => 'security']));
        }

        // Front end redirect
        if ('feRedirect' === $request->query->get('do')) {
            trigger_deprecation('contao/core-bundle', '4.0', 'Using the "feRedirect" parameter has been deprecated and will no longer work in Contao 5.0. Use the "contao_backend_preview" route directly instead.');

            return $this->redirectToFrontendPage($request->query->getInt('page'), $request->query->get('article'));
        }

        // Backend user profile redirect
        if ('login' === $request->query->get('do') && ('edit' !== $request->query->get('act') && $request->query->getInt('id') !== (int) $user->id)) {
            $url = $this->get('router')->generate('contao_backend', [
                'do' => 'login',
                'act' => 'edit',
                'id' => $user->id,
                'ref' => $request->attributes->get('_contao_referer_id'),
                'rt' => REQUEST_TOKEN,
            ]
            );

            return $this->redirect($url);
        }

        $version = PackageUtil::getContaoVersion();

        $template = new BackendTemplate('be_main');

        $template->version = $this->trans('MSC.version').' '.$version;

        // Ajax request
        if (Environment::get('isAjaxRequest') && $request->request->has('action')) {
            $objAjax = new Ajax($request->request->get('action'));
            $objAjax->executePreActions();
        }

        // Toggle nodes
        if ($mtg = $request->query->get('mtg')) {
            /** @var AttributeBagInterface $objSessionBag */
            $objSessionBag = $this->get('session')->getBag('contao_backend');
            $session = $objSessionBag->all();
            $session['backend_modules'][$mtg] = isset($session['backend_modules'][$mtg]) && 0 === $session['backend_modules'][$mtg] ? 1 : 0;
            $objSessionBag->replace($session);

            return $this->redirect(preg_replace('/(&(amp;)?|\?)mtg=[^& ]*/i', '', $request->getRequestUri()));
        }

        // Error
        if ('error' === $request->query->get('act')) {
            $template->error = $this->trans('ERR.general');
            $template->title = $this->trans('ERR.general');

            trigger_deprecation('contao/core-bundle', '4.0', 'Using "act=error" has been deprecated and will no longer work in Contao 5.0. Throw an exception instead.');

            return $this->getResponse($request, $template, $user);
        }

        // Open a module
        if ($request->query->get('do')) {
            if ($request->query->has('picker')) {
                $picker = $this->get('contao.picker.builder')->createFromData($request->query->get('picker'));

                if (null !== $picker && ($menu = $picker->getMenu())) {
                    $template->pickerMenu = $this->menuRenderer->render($menu);
                }
            }

            $template->main = $this->getBackendModule($request->query->get('do'));

            return $this->getResponse($request, $template, $user);
        }

        // Welcome screen
        $template->main = $this->generateDashboard();

        return $this->getResponse($request, $template, $user);
    }

    public static function getSubscribedServices()
    {
        $services = parent::getSubscribedServices();

        $services['translator'] = TranslatorInterface::class;
        $services['contao.picker.builder'] = PickerBuilderInterface::class;
        $services[BackendState::class] = BackendState::class;

        return $services;
    }

    private function getResponse(Request $request, BackendTemplate $template, BackendUser $user): Response
    {
        $template->headline = $this->get(BackendState::class)->getHeadline();
        $template->title = $this->get(BackendState::class)->getTitle();

        // Default headline
        if (!$template->headline) {
            $template->headline = $this->trans('MSC.dashboard');
        }

        // Default title
        if (!$template->title) {
            $template->title = $template->headline;
        }

        // File picker reference (backwards compatibility)
        if ($request->query->has('popup') && 'show' !== $request->query->get('act') && $this->get('session')->get('filePickerRef') && (('page' === $request->query->get('do') && $user->hasAccess('page', 'modules')) || ('files' === $request->query->get('do') && $user->hasAccess('files', 'modules')))) {
            $template->managerHref = StringUtil::ampersand($this->get('session')->get('filePickerRef'));
            $template->manager = false !== strpos($this->get('session')->get('filePickerRef'), 'contao/page?') ? $this->trans('MSC.pagePickerHome') : $this->trans('MSC.filePickerHome');
        }

        $template->theme = Backend::getTheme();
        $template->base = Environment::get('base');
        $template->language = $GLOBALS['TL_LANGUAGE'];
        $template->title = StringUtil::specialchars(strip_tags($template->title));
        $template->host = Backend::getDecodedHostname();
        $template->charset = Config::get('characterSet');
        $template->home = $this->trans('MSC.home');
        $template->isPopup = $request->query->get('popup');
        $template->learnMore = sprintf($this->trans('MSC.learnMore'), '<a href="https://contao.org" target="_blank" rel="noreferrer noopener">contao.org</a>');

        $template->menu = $this->get('twig')->render('@ContaoCore/Backend/be_menu.html.twig');
        $template->headerMenu = $this->get('twig')->render('@ContaoCore/Backend/be_header_menu.html.twig');

        $template->localeString = $template->getLocaleString();
        $template->dateString = $template->getDateString();

        return $template->getResponse();
    }

    private function getBackendModule(string $name)
    {
        if ($this->fragmentRegistry->has(BackendModuleReference::TAG_NAME.'.'.$name)) {
            $reference = new BackendModuleReference($name);

            return $this->fragmentHandler->render($reference, 'forward');
        }

        foreach ($GLOBALS['BE_MOD'] as $group) {
            if (isset($group[$name]) && !empty($group[$name])) {
                $reference = new ControllerReference(BackendModuleController::class);

                return $this->fragmentHandler->render($reference, 'forward');
            }
        }

        throw new \InvalidArgumentException(sprintf('Back end module "%s" is not defined in the BE_MOD array', $name));
    }

    private function generateDashboard(): string
    {
        $widgets = array_values(
            array_filter(
                $this->fragmentRegistry->keys(),
                static function ($key) {
                    return 0 === strpos($key, DashboardWidgetReference::TAG_NAME.'.');
                }
            )
        );

        return implode(
            '',
            array_map(
                function (string $widget): ?string {
                    return $this->fragmentHandler->render(new FragmentReference($widget), 'forward');
                },
                $widgets
            )
        );
    }

    private function redirectToFrontendPage(int $page, ?string $article): RedirectResponse
    {
        if (null === ($pageModel = PageModel::findWithDetails($page))) {
            return new RedirectResponse('');
        }

        $parameters = null;

        // Add the /article/ fragment (see #673)
        if ($article && null !== ($articleModel = ArticleModel::findByAlias($article))) {
            $parameters = sprintf('/articles/%s%s', 'main' !== $articleModel->inColumn ? $articleModel->inColumn.':' : '', $article);
        }

        return new RedirectResponse($pageModel->getPreviewUrl($parameters));
    }

    private function trans(string $key, array $parameters = [], string $domain = 'contao_default')
    {
        return $this->get('translator')->trans($key, $parameters, $domain);
    }
}
