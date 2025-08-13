<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Controller\Page;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Twig\LayoutTemplate;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractLayoutPageController extends AbstractController
{
    public function __construct()
    {
    }

    public function __invoke(Request $request): Response
    {
        if (!$page = $this->container->get('contao.routing.page_finder')->getCurrentPage()) {
            throw $this->createNotFoundException();
        }

        // Get associated layout
        $this->initializeContaoFramework();

        if (!$layout = $this->getContaoAdapter(LayoutModel::class)->findById($page->layout)) {
            throw $this->createNotFoundException();
        }

        // Set the context
        $this->container->get('contao.image.picture_factory')->setDefaultDensities($layout->defaultImageDensities);
        $this->container->get('contao.image.preview_factory')->setDefaultDensities($layout->defaultImageDensities);

        // Create layout template and assign defaults
        $template = $this->createTemplate($layout->template);
        $this->addDefaultDataToTemplate($template, $page, $layout, $this->getResponseContext($page));

        $response = $this->getResponse($template, $layout, $request);
        $this->container->get('contao.routing.response_context_accessor')->finalizeCurrentContext($response);

        return $response;
    }

    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();

        $services['contao.routing.page_finder'] = '?'.PageFinder::class;
        $services['contao.routing.response_context_accessor'] = '?'.ResponseContextAccessor::class;
        $services['contao.routing.response_context_factory'] = '?'.CoreResponseContextFactory::class;
        $services['contao.security.token_checker'] = '?'.TokenChecker::class;
        $services['contao.image.picture_factory'] = '?'.PictureFactoryInterface::class;
        $services['contao.image.preview_factory'] = '?'.PreviewFactory::class;
        $services['contao.assets.assets_context'] = '?'.ContaoContext::class;

        return $services;
    }

    protected function getResponseContext(PageModel $page): ResponseContext
    {
        return $this->container
            ->get('contao.routing.response_context_factory')
            ->createContaoWebpageResponseContext($page)
        ;
    }

    protected function createTemplate(string $identifier): LayoutTemplate
    {
        return new LayoutTemplate(
            $identifier,
            fn (LayoutTemplate $template, Response|null $preBuiltResponse): Response => $this->render("@Contao/{$template->getName()}.html.twig", $template->getData(), $preBuiltResponse),
        );
    }

    protected function addDefaultDataToTemplate(LayoutTemplate $template, PageModel $page, LayoutModel $layout, ResponseContext $responseContext): void
    {
        // Page context
        $template->set('page', $page->row());
        $template->set('layout', $layout->row());

        $template->set('head', $responseContext->get(HtmlHeadBag::class));
        $template->set('preview_mode', $this->container->get('contao.security.token_checker')->isPreviewMode());

        $locale = LocaleUtil::formatAsLocale($page->language);
        $isRtl = 'right-to-left' === (\ResourceBundle::create($locale, 'ICUDATA')['layout']['characters'] ?? null);

        $template->set('locale', $locale);
        $template->set('rtl', $isRtl);

        // Response context
        $template->set('response_context', new class($this->getResponseContextData($responseContext)) {
            /**
             * @param array<string, \Closure():mixed|mixed> $data
             */
            public function __construct(private array $data)
            {
            }

            public function __get(string $key): mixed
            {
                if (!\array_key_exists($key, $this->data)) {
                    return null;
                }

                if ($this->data[$key] instanceof \Closure) {
                    $this->data[$key] = ($this->data[$key])();
                }

                return $this->data[$key] ?? null;
            }

            public function __isset(string $key): bool
            {
                return null !== $this->__get($key);
            }

            /**
             * @interal
             */
            public function all(): \Generator
            {
                foreach (array_keys($this->data) as $key) {
                    yield $key => $this->__get($key);
                }
            }
        });

        // Content composition
        $moduleIdsBySlot = [];

        foreach (StringUtil::deserialize($layout->modules, true) as $definition) {
            if ($definition['enable'] ?? null) {
                $moduleIdsBySlot[$definition['col']][] = (int) $definition['mod'];
            }
        }

        $template->set('modules', $moduleIdsBySlot);

        foreach ($moduleIdsBySlot as $slot => $moduleIds) {
            // We use a lazy value here, so that modules won't get rendered if not requested.
            // This is for instance the case if the slot's content was defined explicitly.
            $lazyValue = new class(fn () => $this->renderSlot($slot, $moduleIds)) {
                public function __construct(private \Closure|string $value)
                {
                }

                public function __toString(): string
                {
                    if ($this->value instanceof \Closure) {
                        $this->value = ($this->value)();
                    }

                    return $this->value;
                }
            };

            $template->setSlot($slot, $lazyValue);
        }
    }

    /**
     * @return array<string, \Closure():mixed|mixed>
     */
    protected function getResponseContextData(ResponseContext $responseContext): array
    {
        return [
            'head' => static fn () => $responseContext->get(HtmlHeadBag::class),
            'end_of_head' => fn () => [
                ...array_map(
                    function (string $url): string {
                        $options = StringUtil::resolveFlaggedUrl($url);

                        if (!Path::isAbsolute($url) && $staticUrl = $this->container->get('contao.assets.assets_context')->getStaticUrl()) {
                            $url = Path::join($staticUrl, $url);
                        }

                        return Template::generateStyleTag($url, $options->media, $options->mtime);
                    },
                    array_unique($GLOBALS['TL_CSS'] ?? []),
                ),
                ...$GLOBALS['TL_STYLE_SHEETS'] ?? [],
                ...$GLOBALS['TL_HEAD'] ?? [],
            ],
            'end_of_body' => static fn () => $GLOBALS['TL_BODY'] ?? [],
            'json_ld_scripts' => static fn () => $responseContext->isInitialized(JsonLdManager::class)
                ? $responseContext->get(JsonLdManager::class)->collectFinalScriptFromGraphs()
                : null,
        ];
    }

    abstract protected function getResponse(LayoutTemplate $template, LayoutModel $model, Request $request): Response;

    /**
     * Renders a template. If $view is set to null, the default template of this
     * fragment will be rendered.
     */
    protected function render(string|null $view = null, array $parameters = [], Response|null $response = null): Response
    {
        $view ??= $this->view ?? throw new \InvalidArgumentException('Cannot derive template name, please make sure createTemplate() was called before or specify the template explicitly.');

        return parent::render($view, $parameters, $response);
    }

    /**
     * @param list<int> $moduleIds
     */
    protected function renderSlot(string $slot, array $moduleIds, string $identifier = 'frontend_module/module_group'): string
    {
        $result = $this->renderView("@Contao/$identifier.html.twig", [
            '_slot_name' => $slot,
            'modules' => $moduleIds,
        ]);

        // If there is no non-whitespace character, do not output the slot at all
        if (preg_match('/^\s*$/', $result)) {
            return '';
        }

        return $result;
    }
}
