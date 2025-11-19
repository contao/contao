<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Controller\Page;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\EventListener\SubrequestCacheSubscriber;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Twig\Defer\Renderer;
use Contao\CoreBundle\Twig\LayoutTemplate;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
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

        // Load contao_default translations (#8690)
        $this->getContaoAdapter(System::class)->loadLanguageFile('default');

        // Set the context
        $this->container->get('contao.image.picture_factory')->setDefaultDensities($layout->defaultImageDensities);
        $this->container->get('contao.image.preview_factory')->setDefaultDensities($layout->defaultImageDensities);

        // Create layout template and assign defaults
        $template = $this->createTemplate($layout->template);
        $this->addDefaultDataToTemplate($template, $page, $layout, $this->getResponseContext($page));

        $response = $this->getResponse($template, $layout, $request);
        $this->container->get('contao.routing.response_context_accessor')->finalizeCurrentContext($response);

        // Set cache headers
        $response->headers->set(SubrequestCacheSubscriber::MERGE_CACHE_HEADER, '1');
        $this->setCacheHeaders($response, $page);

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
        $services['contao.twig.defer.renderer'] = '?'.Renderer::class;

        return $services;
    }

    protected function getResponseContext(PageModel $page): ResponseContext
    {
        if ($responseContext = $this->container->get('contao.routing.response_context_accessor')->getResponseContext()) {
            return $responseContext;
        }

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
        $elementReferencesBySlot = [];

        foreach (StringUtil::deserialize($layout->modules, true) as $definition) {
            if ($definition['enable'] ?? false) {
                $isContentElement = str_starts_with($definition['mod'], 'content-');

                $elementReferencesBySlot[$definition['col']][] = [
                    'type' => $isContentElement ? 'content_element' : 'frontend_module',
                    'id' => (int) ($isContentElement ? substr($definition['mod'], 8) : $definition['mod']),
                ];
            }
        }

        $template->set('element_references', $elementReferencesBySlot);

        foreach ($elementReferencesBySlot as $slot => $elementIds) {
            // We use a lazy value here, so that modules won't get rendered if not requested.
            // This is for instance the case if the slot's content was defined explicitly.
            $lazyValue = new class(fn () => $this->renderSlot($slot, $elementIds)) {
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
            'head' => $responseContext->get(HtmlHeadBag::class),
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

        // The abstract parent class does several things when rendering a template (like
        // handling Symfony forms) but uses the default Twig environment, which would
        // output the content in a linear fashion. In order to use our own renderer
        // capable of deferred blocks, we first render an empty template using the
        // default method and then set our own content.
        $response = parent::render('@ContaoCore/blank.html.twig', $parameters, $response);
        $response->setContent($this->container->get('contao.twig.defer.renderer')->render($view, $parameters));

        return $response;
    }

    /**
     * @param list<array{type: string, id: int}> $elementReferences
     */
    protected function renderSlot(string $slot, array $elementReferences, string $identifier = 'layout/_element_group'): string
    {
        $result = $this->renderView("@Contao/$identifier.html.twig", [
            '_slot_name' => $slot,
            'references' => $elementReferences,
        ]);

        // If there is no non-whitespace character, do not output the slot at all
        if (preg_match('/^\s*$/', $result)) {
            return '';
        }

        return $result;
    }
}
