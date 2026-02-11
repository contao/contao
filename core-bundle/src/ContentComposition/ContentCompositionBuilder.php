<?php

declare(strict_types=1);

namespace Contao\CoreBundle\ContentComposition;

use Contao\Config;
use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Exception\NoLayoutSpecifiedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Twig\LayoutTemplate;
use Contao\CoreBundle\Twig\Renderer\RendererInterface;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Contao\ThemeModel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * @experimental
 */
class ContentCompositionBuilder
{
    private string|null $layoutTemplate = null;

    private ResponseContext|null $responseContext = null;

    private string|null $defaultImageDensities = null;

    private RendererInterface $slotRenderer;

    /**
     * @param array<array-key, array{type: "content_element"|"frontend_module", id: int}> $framework
     */
    private array $elementReferencesBySlot = [];

    private string $slotTemplate = 'page/_element_group';

    /**
     * @internal
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly LoggerInterface $logger,
        private readonly PictureFactory $pictureFactory,
        private readonly PreviewFactory $previewFactory,
        private readonly ContaoContext $assetsContext,
        private RendererInterface $renderer,
        private readonly RequestStack $requestStack,
        private readonly LocaleAwareInterface $translator,
        private readonly PageModel $page,
    ) {
        $this->slotRenderer = $this->renderer;
    }

    /**
     * Set a custom layout template to use during build instead of trying to find an
     * associated page layout and using its data. Use this option for your own page
     * controllers that support content composition but do not have a layout.
     */
    public function useCustomLayoutTemplate(string $identifier): self
    {
        $this->layoutTemplate = $identifier;

        return $this;
    }

    /**
     * If a response context is set, default data queried from it will be available in
     * the template through lazy accessors.
     */
    public function setResponseContext(ResponseContext $responseContext): self
    {
        $this->responseContext = $responseContext;

        return $this;
    }

    /**
     * Set the default image densities that the image libraries will get configured
     * with on build.
     */
    public function setDefaultImageDensities(string $densities): self
    {
        $this->defaultImageDensities = $densities;

        return $this;
    }

    /**
     * Set the renderer that will be used to render the layout template.
     */
    public function setRenderer(RendererInterface $renderer): self
    {
        $this->renderer = $renderer;

        return $this;
    }

    /**
     * Set the renderer that will be used to render the slot template.
     */
    public function setSlotRenderer(RendererInterface $renderer): self
    {
        $this->slotRenderer = $renderer;

        return $this;
    }

    /**
     * Add a content element or frontend module - referenced by their ID - to a
     * certain slot. References and stringable objects that lazily render the slots
     * will be available as template parameters.
     */
    public function addElementToSlot(string $slot, int $id, bool $isContentElement = true): self
    {
        $this->elementReferencesBySlot[$slot][] = [
            'type' => $isContentElement ? 'content_element' : 'frontend_module',
            'id' => $id,
        ];

        return $this;
    }

    /**
     * Shortcut to add the article dummy frontend module to a certain slot.
     */
    public function addArticleToSlot(string $slot): self
    {
        $this->addElementToSlot($slot, 0, false);

        return $this;
    }

    /**
     * Set the template identifier of the slot template to be used.
     */
    public function setSlotTemplate(string $identifier): self
    {
        $this->slotTemplate = $identifier;

        return $this;
    }

    /**
     * Configure the framework, gather data and create a fully configured layout template.
     */
    public function buildLayoutTemplate(): LayoutTemplate
    {
        $this->framework->initialize();

        // If no template was explicitly set, we try to find the associated user layout
        // and gather the settings from it
        if (null === $this->layoutTemplate) {
            if (!$layout = $this->framework->getAdapter(LayoutModel::class)->findById($this->page->layout)) {
                $this->logger->error(\sprintf('Could not find layout ID "%s"', $this->page->layout));

                throw new NoLayoutSpecifiedException('No layout specified');
            }

            // Guard against using anything other than the modern layout
            if ('modern' !== $layout->type) {
                throw new \LogicException(\sprintf('Layout type "%s" is not supported in the %s.', $layout->type, self::class));
            }

            $this->layoutTemplate = $layout->template;
            $this->defaultImageDensities ??= $layout->defaultImageDensities;

            // Add slot content
            foreach (StringUtil::deserialize($layout->modules, true) as $definition) {
                if ($definition['enable'] ?? false) {
                    $isContentElement = str_starts_with($definition['mod'], 'content-');

                    $this->addElementToSlot(
                        $definition['col'],
                        (int) ($isContentElement ? substr($definition['mod'], 8) : $definition['mod']),
                        $isContentElement,
                    );
                }
            }

            // Backwards compatibility: set layout information on PageModel
            $this->page->layoutId = $layout->id;
            $this->page->template = $layout->template;
            $this->page->templateGroup = $this->framework->getAdapter(ThemeModel::class)->findById($layout->pid)?->templates;
        } else {
            $layout = null;
        }

        // Configure services and set globals
        $this->setupFramework($this->page);

        // Create the template and add default data
        $template = new LayoutTemplate(
            $this->layoutTemplate,
            function (LayoutTemplate $template, Response|null $preBuiltResponse): Response {
                $response = $preBuiltResponse ?? new Response();
                $parameters = $template->getData();
                $content = $this->renderer->render("@Contao/{$template->getName()}.html.twig", $parameters);

                if (200 === $response->getStatusCode()) {
                    foreach ($parameters as $v) {
                        if ($v instanceof FormInterface && $v->isSubmitted() && !$v->isValid()) {
                            $response->setStatusCode(422);
                            break;
                        }
                    }
                }

                $response->setContent($content);

                return $response;
            },
        );

        $this->addDefaultDataToTemplate($template, $this->page, $layout);
        $this->addCompositedContentToTemplate($template, $this->elementReferencesBySlot);
        $this->addResponseContextToTemplate($template, $this->responseContext);

        return $template;
    }

    private function setupFramework(PageModel $page): void
    {
        // Set global variables
        $page->loadDetails();

        if ($page->adminEmail) {
            [$GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']] = StringUtil::splitFriendlyEmail($page->adminEmail);
        } else {
            [$GLOBALS['TL_ADMIN_NAME'], $GLOBALS['TL_ADMIN_EMAIL']] = StringUtil::splitFriendlyEmail(Config::get('adminEmail'));
        }

        // Deprecated since Contao 4.0, to be removed in Contao 6.0
        $GLOBALS['TL_LANGUAGE'] = LocaleUtil::formatAsLanguageTag($page->language ?? '');

        // Set locale
        $locale = LocaleUtil::formatAsLocale($page->language ?? '');

        $this->requestStack->getCurrentRequest()?->setLocale($locale);
        $this->translator->setLocale($locale);

        // Load contao_default translations (#8690)
        $this->framework->getAdapter(System::class)->loadLanguageFile('default');

        // Configure image library defaults
        if ($this->defaultImageDensities) {
            $this->pictureFactory->setDefaultDensities($this->defaultImageDensities);
            $this->previewFactory->setDefaultDensities($this->defaultImageDensities);
        }

        // Backwards compatibility: make global $objPage available
        global $objPage;
        $objPage = $page;
    }

    private function addDefaultDataToTemplate(LayoutTemplate $template, PageModel $page, LayoutModel|null $layout): void
    {
        $locale = LocaleUtil::formatAsLocale($page->language ?? '');
        $isRtl = 'right-to-left' === (\ResourceBundle::create($locale, 'ICUDATA')['layout']['characters'] ?? null);

        $template->set('locale', $locale);
        $template->set('rtl', $isRtl);

        $template->set('page', $page->row());

        if ($layout) {
            $template->set('layout', $layout->row());
        }
    }

    private function addResponseContextToTemplate(LayoutTemplate $template, ResponseContext|null $responseContext): void
    {
        if (!$responseContext) {
            return;
        }

        $responseContextData = [
            'head' => $responseContext->has(HtmlHeadBag::class) ? $responseContext->get(HtmlHeadBag::class) : null,
            'end_of_head' => fn () => [
                ...array_map(
                    function (string $url): string {
                        $options = StringUtil::resolveFlaggedUrl($url);

                        if (!Path::isAbsolute($url) && $staticUrl = $this->assetsContext->getStaticUrl()) {
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

        $template->set('response_context', new class($responseContextData) {
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

            public function __set(string $key, mixed $value): void
            {
                throw new \InvalidArgumentException(\sprintf('Cannot set readonly property "%s".', $key));
            }

            public function __isset(string $key): bool
            {
                return null !== $this->__get($key);
            }

            /**
             * @internal
             */
            public function all(): \Generator
            {
                foreach (array_keys($this->data) as $key) {
                    yield $key => $this->__get($key);
                }
            }
        });
    }

    private function addCompositedContentToTemplate(LayoutTemplate $template, array $elementReferencesBySlot): void
    {
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
     * @param list<array{type: string, id: int}> $elementReferences
     */
    private function renderSlot(string $slot, array $elementReferences): string
    {
        $result = $this->slotRenderer->render("@Contao/$this->slotTemplate.html.twig", [
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
