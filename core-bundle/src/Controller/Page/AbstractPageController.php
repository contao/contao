<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Controller\Page;

use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Twig\LayoutTemplate;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractPageController extends AbstractController
{
    protected ResponseContext|null $responseContext = null;

    public function __invoke(Request $request): Response
    {
        $page = $this->getPageModel();

        // Get layout
        $layoutAdapter = $this->getContaoAdapter(LayoutModel::class);
        $layout = $layoutAdapter->findByPk($page->layout);

        // Set the context
        $responseContext = $this->getResponseContext($page);
        $page->templateGroup = $layout->getRelated('pid')->templates ?? null;
        $this->container->get('contao.image.picture_factory')->setDefaultDensities($layout->defaultImageDensities);
        $this->container->get('contao.image.preview_factory')->setDefaultDensities($layout->defaultImageDensities);

        // Create layout template and assign defaults
        $template = $this->createTemplate($layout->template);
        $this->addDefaultDataToTemplate($template, $page, $layout, $responseContext);

        return $this->getResponse($template, $layout, $request);
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.routing.response_context_factory'] = CoreResponseContextFactory::class;
        $services['contao.security.token_checker'] = TokenChecker::class;
        $services['contao.image.picture_factory'] = PictureFactoryInterface::class;
        $services['contao.image.preview_factory'] = PreviewFactory::class;

        return $services;
    }

    abstract protected function getResponse(LayoutTemplate $template, LayoutModel $model, Request $request): Response;

    protected function createTemplate(string $identifier): LayoutTemplate
    {
        return new LayoutTemplate(
            $identifier,
            fn (LayoutTemplate $template, Response|null $preBuiltResponse): Response => $this->render("@Contao/{$template->getName()}.html.twig", $template->getData(), $preBuiltResponse),
        );
    }

    protected function getResponseContext(PageModel $page): ResponseContext
    {
        // Initialize response context
        return $this->container
            ->get('contao.routing.response_context_factory')
            ->createContaoWebpageResponseContext($page)
        ;
    }

    protected function addDefaultDataToTemplate(LayoutTemplate $template, PageModel $page, LayoutModel $layout, ResponseContext $responseContext): void
    {
        // Slots
        $moduleIdsBySlot = [];

        foreach (StringUtil::deserialize($layout->modules, true) as $definition) {
            if ($definition['enable']) {
                $id = (int) $definition['mod'];
                $moduleIdsBySlot[$definition['col']][] = $id;
            }
        }

        foreach ($moduleIdsBySlot as $slot => $moduleIds) {
            $template->setSlot($slot, $this->composeModulesForSlot($slot, $moduleIds));
        }

        // Chrome
        $template->set('page', $page);
        $template->set('layout', $layout);

        $template->set('head', $responseContext->get(HtmlHeadBag::class));
        $template->set('preview_mode', $this->container->get('contao.security.token_checker')->isPreviewMode());

        $locale = LocaleUtil::formatAsLocale($page->language);
        $isRtl = (\ResourceBundle::create($locale, 'ICUDATA')['layout']['characters'] ?? null) === 'right-to-left';

        $template->set('locale', $locale);
        $template->set('rtl', $isRtl);
    }

    /**
     * Renders a template. If $view is set to null, the default template of
     * this fragment will be rendered.
     */
    protected function render(string|null $view = null, array $parameters = [], Response|null $response = null): Response
    {
        $view ??= $this->view ?? throw new \InvalidArgumentException('Cannot derive template name, please make sure createTemplate() was called before or specify the template explicitly.');

        return parent::render($view, $parameters, $response);
    }

    /**
     * @param list<int> $moduleIds
     */
    protected function composeModulesForSlot(string $slot, array $moduleIds, string $identifier = 'framework/module_composition'): string
    {
        return $this->renderView("@Contao/$identifier.html.twig", [
            'slot' => $slot,
            'modules' => $moduleIds,
        ]);
    }
}
