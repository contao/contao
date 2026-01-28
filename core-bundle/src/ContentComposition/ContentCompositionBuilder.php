<?php

declare(strict_types=1);

namespace Contao\CoreBundle\ContentComposition;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Exception\NoLayoutSpecifiedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Image\Preview\PreviewFactory;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Twig\LayoutTemplate;
use Contao\CoreBundle\Twig\Renderer\RendererInterface;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Template;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @experimental
 */
class ContentCompositionBuilder
{
    private RendererInterface|null $fragmentRenderer = null;

    private ResponseContext|null $responseContext = null;

    private string $slotTemplate = 'page/_element_group';

    /**
     * @internal
     */
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly LoggerInterface $logger,
        private readonly PictureFactory $pictureFactory,
        private readonly PreviewFactory $previewFactory,
        private readonly TokenChecker $tokenChecker,
        private readonly ContaoContext $assetsContext,
        private RendererInterface $renderer,
        private readonly PageModel $page,
    ) {
    }

    public function setResponseContext(ResponseContext $responseContext): self
    {
        $this->responseContext = $responseContext;

        return $this;
    }

    public function setRenderer(RendererInterface $renderer): self
    {
        $this->renderer = $renderer;

        return $this;
    }

    public function setFragmentRenderer(RendererInterface $renderer): self
    {
        $this->fragmentRenderer = $renderer;

        return $this;
    }

    public function setSlotTemplate(string $identifier): self
    {
        $this->slotTemplate = $identifier;

        return $this;
    }

    public function buildLayoutTemplate(): LayoutTemplate
    {
        $this->framework->initialize();

        if (!$layout = $this->framework->getAdapter(LayoutModel::class)->findById($this->page->layout)) {
            $this->logger->error(\sprintf('Could not find layout ID "%s"', $this->page->layout));

            throw new NoLayoutSpecifiedException('No layout specified');
        }

        // Load contao_default translations (#8690)
        $this->framework->getAdapter(System::class)->loadLanguageFile('default');

        // Set the context
        $this->pictureFactory->setDefaultDensities($layout->defaultImageDensities);
        $this->previewFactory->setDefaultDensities($layout->defaultImageDensities);

        // Create the template and add default data
        $template = new LayoutTemplate(
            $layout->template,
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

        $this->addPageContextToTemplate($template, $this->page, $layout);
        $this->addCompositedContentToTemplate($template, $layout);

        if ($this->responseContext) {
            $this->addResponseContextToTemplate($template, $this->responseContext);
        }

        return $template;
    }

    private function addPageContextToTemplate(LayoutTemplate $template, PageModel $page, LayoutModel $layout): void
    {
        $template->set('page', $page->row());
        $template->set('layout', $layout->row());

        $template->set('preview_mode', $this->tokenChecker->isPreviewMode());

        $locale = LocaleUtil::formatAsLocale($page->language);
        $isRtl = 'right-to-left' === (\ResourceBundle::create($locale, 'ICUDATA')['layout']['characters'] ?? null);

        $template->set('locale', $locale);
        $template->set('rtl', $isRtl);
    }

    private function addResponseContextToTemplate(LayoutTemplate $template, ResponseContext $responseContext): void
    {
        $responseContextData = [
            'head' => $responseContext->get(HtmlHeadBag::class),
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

    private function addCompositedContentToTemplate(LayoutTemplate $template, LayoutModel $layout): void
    {
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
     * @param list<array{type: string, id: int}> $elementReferences
     */
    private function renderSlot(string $slot, array $elementReferences): string
    {
        $result = ($this->fragmentRenderer ?? $this->renderer)->render("@Contao/{$this->slotTemplate}.html.twig", [
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
