<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Extension;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DataContainer\DataContainerOperationsBuilder;
use Contao\CoreBundle\InsertTag\ChunkedText;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Contao\CoreBundle\Twig\Defer\DeferTokenParser;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inheritance\DynamicExtendsTokenParser;
use Contao\CoreBundle\Twig\Inheritance\DynamicIncludeTokenParser;
use Contao\CoreBundle\Twig\Inheritance\DynamicUseTokenParser;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\ResponseContext\AddTokenParser;
use Contao\CoreBundle\Twig\ResponseContext\DocumentLocation;
use Contao\CoreBundle\Twig\Runtime\BackendHelperRuntime;
use Contao\CoreBundle\Twig\Runtime\ContentUrlRuntime;
use Contao\CoreBundle\Twig\Runtime\CspRuntime;
use Contao\CoreBundle\Twig\Runtime\FigureRuntime;
use Contao\CoreBundle\Twig\Runtime\FormatterRuntime;
use Contao\CoreBundle\Twig\Runtime\FragmentRuntime;
use Contao\CoreBundle\Twig\Runtime\HighlighterRuntime;
use Contao\CoreBundle\Twig\Runtime\HighlightResult;
use Contao\CoreBundle\Twig\Runtime\InsertTagRuntime;
use Contao\CoreBundle\Twig\Runtime\LegacyTemplateFunctionsRuntime;
use Contao\CoreBundle\Twig\Runtime\PictureConfigurationRuntime;
use Contao\CoreBundle\Twig\Runtime\SchemaOrgRuntime;
use Contao\CoreBundle\Twig\Runtime\StringRuntime;
use Contao\CoreBundle\Twig\Runtime\UrlRuntime;
use Contao\CoreBundle\Twig\Slots\SlotTokenParser;
use Contao\StringUtil;
use Twig\DeprecatedCallableInfo;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\Extension\EscaperExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Runtime\EscaperRuntime;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class ContaoExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Environment $environment,
        private readonly ContaoFilesystemLoader $filesystemLoader,
        private readonly ContaoVariable $contaoVariable,
        private readonly InspectorNodeVisitor $inspectorNodeVisitor,
    ) {
        // Mark classes as safe for HTML that already escape their output themselves
        $escaperRuntime = $this->environment->getRuntime(EscaperRuntime::class);

        $escaperRuntime->addSafeClass(HtmlAttributes::class, ['html', 'contao_html']);
        $escaperRuntime->addSafeClass(HighlightResult::class, ['html', 'contao_html']);
        $escaperRuntime->addSafeClass(DataContainerOperationsBuilder::class, ['html', 'contao_html']);
    }

    public function getGlobals(): array
    {
        return ['contao' => $this->contaoVariable];
    }

    public function getNodeVisitors(): array
    {
        return [
            // Records data about a template during compilation, so that they get available
            // at runtime
            $this->inspectorNodeVisitor,
            // Triggers PHP deprecations if deprecated constructs are found in the
            // parsed templates.
            new DeprecationsNodeVisitor(),
        ];
    }

    public function getTokenParsers(): array
    {
        return [
            // Overwrite the parsers for the "extends", "include" and "use" tags to
            // additionally support the Contao template hierarchy
            new DynamicExtendsTokenParser($this->filesystemLoader),
            new DynamicIncludeTokenParser($this->filesystemLoader),
            new DynamicUseTokenParser($this->filesystemLoader),
            // Add a parser for the Contao specific "add" tag
            new AddTokenParser(self::class),
            // Add a parser for the Contao specific "slot" tag
            new SlotTokenParser(),
            // Add a parser for the Contao specific "defer" tag
            new DeferTokenParser(),
        ];
    }

    public function getFunctions(): array
    {
        $includeFunctionCallable = $this->getTwigIncludeFunction()->getCallable();

        return [
            // Overwrite the "include" function to additionally support the Contao
            // template hierarchy
            new TwigFunction(
                'include',
                function (Environment $env, $context, $template, $variables = [], $withContext = true, $ignoreMissing = false, $sandboxed = false) use ($includeFunctionCallable) {
                    $args = \func_get_args();

                    if (\is_string($template)) {
                        $parts = ContaoTwigUtil::parseContaoName($template);

                        if ('Contao' === ($parts[0] ?? null)) {
                            $candidates = $this->filesystemLoader->getAllFirstByThemeSlug($parts[1] ?? '');
                            $args[2] = $candidates[$this->filesystemLoader->getCurrentThemeSlug() ?? ''] ?? $candidates[''];
                        }
                    }

                    return $includeFunctionCallable(...$args);
                },
                ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['all']],
            ),
            new TwigFunction(
                'attrs',
                static fn (HtmlAttributes|iterable|string|null $attributes = null): HtmlAttributes => new HtmlAttributes($attributes),
            ),
            new TwigFunction(
                'figure',
                [FigureRuntime::class, 'buildFigure'],
            ),
            new TwigFunction(
                'contao_figure',
                [FigureRuntime::class, 'renderFigure'],
                [
                    'is_safe' => ['html'],
                    'deprecation_info' => new DeprecatedCallableInfo('contao/core-bundle', '5.0', 'figure'),
                ],
            ),
            new TwigFunction(
                'picture_config',
                [PictureConfigurationRuntime::class, 'fromArray'],
            ),
            new TwigFunction(
                'insert_tag',
                [InsertTagRuntime::class, 'renderInsertTag'],
            ),
            new TwigFunction(
                'add_schema_org',
                [SchemaOrgRuntime::class, 'add'],
            ),
            new TwigFunction(
                'contao_sections',
                [LegacyTemplateFunctionsRuntime::class, 'renderLayoutSections'],
                ['needs_context' => true, 'is_safe' => ['html']],
            ),
            new TwigFunction(
                'contao_section',
                [LegacyTemplateFunctionsRuntime::class, 'renderLayoutSection'],
                ['needs_context' => true, 'is_safe' => ['html']],
            ),
            new TwigFunction(
                'prefix_url',
                [UrlRuntime::class, 'prefixUrl'],
            ),
            new TwigFunction(
                'frontend_module',
                [FragmentRuntime::class, 'renderModule'],
                ['needs_context' => true, 'is_safe' => ['html']],
            ),
            new TwigFunction(
                'content_element',
                [FragmentRuntime::class, 'renderContent'],
                ['is_safe' => ['html']],
            ),
            // Overwrites the 'csp_nonce' method from nelmio/security-bundle
            new TwigFunction(
                'csp_nonce',
                [CspRuntime::class, 'getNonce'],
            ),
            new TwigFunction(
                'csp_source',
                [CspRuntime::class, 'addSource'],
            ),
            new TwigFunction(
                'csp_hash',
                [CspRuntime::class, 'addHash'],
            ),
            new TwigFunction(
                'content_url',
                [ContentUrlRuntime::class, 'generate'],
            ),
            new TwigFunction(
                'slot',
                static fn () => throw new SyntaxError('You cannot use the slot() function outside of a slot.'),
            ),
            // Backend functions
            new TwigFunction(
                'backend_icon',
                [BackendHelperRuntime::class, 'icon'],
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'file_icon',
                [BackendHelperRuntime::class, 'fileIcon'],
                ['is_safe' => ['html']],
            ),
        ];
    }

    public function getFilters(): array
    {
        $escaperFilter = static function (Environment $env, $string, string $strategy = 'html', string|null $charset = null, bool $autoescape = false) {
            $runtime = $env->getRuntime(EscaperRuntime::class);

            if ($string instanceof ChunkedText) {
                $parts = [];

                foreach ($string as [$type, $chunk]) {
                    if (ChunkedText::TYPE_RAW === $type) {
                        $parts[] = $chunk;
                    } else {
                        $parts[] = $runtime->escape($chunk, $strategy, $charset);
                    }
                }

                return implode('', $parts);
            }

            return $runtime->escape($string, $strategy, $charset, $autoescape);
        };

        return [
            // Overwrite the "escape" filter to additionally support chunked text and our
            // escaper strategies
            new TwigFilter(
                'escape',
                $escaperFilter,
                ['needs_environment' => true, 'is_safe_callback' => EscaperExtension::escapeFilterIsSafe(...)],
            ),
            new TwigFilter(
                'e',
                $escaperFilter,
                ['needs_environment' => true, 'is_safe_callback' => EscaperExtension::escapeFilterIsSafe(...)],
            ),
            new TwigFilter(
                'insert_tag',
                [InsertTagRuntime::class, 'replaceInsertTags'],
                ['needs_context' => true, 'preserves_safety' => ['html']],
            ),
            new TwigFilter(
                'insert_tag_raw',
                [InsertTagRuntime::class, 'replaceInsertTagsChunkedRaw'],
                ['needs_context' => true, 'preserves_safety' => ['html']],
            ),
            new TwigFilter(
                'highlight',
                [HighlighterRuntime::class, 'highlight'],
            ),
            new TwigFilter(
                'highlight_auto',
                [HighlighterRuntime::class, 'highlightAuto'],
            ),
            new TwigFilter(
                'format_bytes',
                [FormatterRuntime::class, 'formatBytes'],
                ['is_safe' => ['html']],
            ),
            new TwigFilter(
                'format_number',
                [FormatterRuntime::class, 'formatNumber'],
                ['is_safe' => ['html']],
            ),
            new TwigFilter(
                'csp_unsafe_inline_style',
                [CspRuntime::class, 'unsafeInlineStyle'],
                ['preserves_safety' => ['html']],
            ),
            new TwigFilter(
                'csp_inline_styles',
                [CspRuntime::class, 'inlineStyles'],
                ['preserves_safety' => ['html']],
            ),
            new TwigFilter(
                'encode_email',
                [StringRuntime::class, 'encodeEmail'],
                ['preserves_safety' => ['contao_html', 'html']],
            ),
            new TwigFilter(
                'input_encoded_to_plain_text',
                [StringRuntime::class, 'inputEncodedToPlainText'],
            ),
            new TwigFilter(
                'html_to_plain_text',
                [StringRuntime::class, 'htmlToPlainText'],
            ),
            new TwigFilter(
                'deserialize',
                static fn (mixed $value): array => StringUtil::deserialize($value, true),
            ),
        ];
    }

    /**
     * @internal
     */
    public function getCurrentThemeSlug(): string|null
    {
        return $this->filesystemLoader->getCurrentThemeSlug();
    }

    /**
     * @see AddNode
     * @see AddTokenParser
     *
     * @internal
     */
    public function addDocumentContent(string|null $identifier, string $content, DocumentLocation $location): void
    {
        // TODO: This should make use of the response context in the future.
        if (DocumentLocation::head === $location) {
            if (null !== $identifier) {
                $GLOBALS['TL_HEAD'][$identifier] = $content;
            } else {
                $GLOBALS['TL_HEAD'][] = $content;
            }
        } elseif (DocumentLocation::endOfBody === $location) {
            if (null !== $identifier) {
                $GLOBALS['TL_BODY'][$identifier] = $content;
            } else {
                $GLOBALS['TL_BODY'][] = $content;
            }
        } elseif (DocumentLocation::stylesheets === $location) {
            if (null !== $identifier) {
                $GLOBALS['TL_STYLE_SHEETS'][$identifier] = $content;
            } else {
                $GLOBALS['TL_STYLE_SHEETS'][] = $content;
            }
        }
    }

    private function getTwigIncludeFunction(): TwigFunction
    {
        foreach ($this->environment->getExtension(CoreExtension::class)->getFunctions() as $function) {
            if ('include' === $function->getName()) {
                return $function;
            }
        }

        throw new \RuntimeException(\sprintf('The %s class was expected to register the "include" Twig function but did not.', CoreExtension::class));
    }
}
