<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Controller;

use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Contao\CoreBundle\Twig\Finder\Finder;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\CoreBundle\Twig\Inspector\BlockInformation;
use Contao\CoreBundle\Twig\Inspector\BlockType;
use Contao\CoreBundle\Twig\Inspector\InspectionException;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\CoreBundle\Twig\Studio\Autocomplete;
use Contao\CoreBundle\Twig\Studio\Operation\OperationContext;
use Contao\CoreBundle\Twig\Studio\Operation\OperationContextFactory;
use Contao\CoreBundle\Twig\Studio\Operation\OperationInterface;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Error\Error;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @experimental
 */
#[IsGranted('ROLE_ADMIN', message: 'Access restricted to administrators.')]
class BackendTemplateStudioController extends AbstractBackendController
{
    /**
     * @var array<string, OperationInterface>
     */
    private array $operations;

    /**
     * @param iterable<string, OperationInterface> $operations
     */
    public function __construct(
        private readonly ContaoFilesystemLoader $loader,
        private readonly FinderFactory $finder,
        private readonly Inspector $inspector,
        private readonly ThemeNamespace $themeNamespace,
        private readonly OperationContextFactory $operationContextFactory,
        private readonly Autocomplete $autocomplete,
        private readonly Connection $connection,
        iterable $operations,
    ) {
        $this->operations = $operations instanceof \Traversable ? iterator_to_array($operations) : $operations;
    }

    #[Route(
        '/%contao.backend.route_prefix%/template-studio',
        name: 'contao_template_studio',
        defaults: ['_scope' => 'backend'],
        methods: ['GET'],
    )]
    public function __invoke(): Response
    {
        $availableThemes = $this->getAvailableThemes();
        $themeContext = $this->getThemeContext();

        // Reset theme context if not existent
        if (!isset($availableThemes[$themeContext])) {
            $this->setThemeContext($themeContext = null);
        }

        return $this->render('@Contao/backend/template_studio/index.html.twig', [
            'title' => 'Template Studio',
            'headline' => 'Template Studio',
            'tree' => $this->generateTree(),
            'themes' => $availableThemes,
            'current_theme' => $themeContext,
        ]);
    }

    /**
     * Stream a prefix tree of template identifiers.
     */
    #[Route(
        '/%contao.backend.route_prefix%/template-studio-tree',
        name: '_contao_template_studio_tree.stream',
        defaults: ['_scope' => 'backend', '_store_referrer' => false],
        methods: ['GET'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function tree(): Response
    {
        return $this->render('@Contao/backend/template_studio/tree/tree.stream.html.twig', [
            'tree' => $this->generateTree(),
        ]);
    }

    /**
     * (De-)select a theme and stream the changes to all open tabs.
     */
    #[Route(
        '/%contao.backend.route_prefix%/template-studio/select_theme',
        name: '_contao_template_studio_select_theme.stream',
        defaults: ['_scope' => 'backend', '_token_check' => false, '_store_referrer' => false],
        methods: ['POST'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function selectTheme(Request $request, #[MapQueryParameter('open_tab')] array $openTabs = []): Response
    {
        $slug = $request->request->getString('theme') ?: null;

        if (null !== $slug && !isset($this->getAvailableThemes()[$slug])) {
            return new Response('The given theme slug is not valid.', Response::HTTP_FORBIDDEN);
        }

        $this->setThemeContext($slug);

        return $this->render('@Contao/backend/template_studio/select_theme.stream.html.twig', [
            'tree' => $this->generateTree(),
            'open_tabs' => array_filter($openTabs, $this->isAllowedIdentifier(...)),
        ]);
    }

    /**
     * Stream an editor tab for the given identifier.
     */
    #[Route(
        '/%contao.backend.route_prefix%/template-studio/resource/{identifier}',
        name: '_contao_template_studio_editor_tab.stream',
        requirements: ['identifier' => '.+'],
        defaults: ['_scope' => 'backend', '_store_referrer' => false],
        methods: ['GET'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function editorTab(string $identifier): Response
    {
        if (!$this->isAllowedIdentifier($identifier)) {
            return $this->render(
                '@Contao/backend/template_studio/editor/failed_to_open_tab.stream.html.twig',
                ['identifier' => $identifier],
            );
        }

        $themeSlug = $this->getThemeContext();
        $logicalNamesChain = array_values($this->loader->getInheritanceChains($themeSlug)[$identifier]);
        $operationContext = $this->getOperationContext($identifier);

        $operationNames = array_keys(
            array_filter(
                $this->operations,
                static fn (OperationInterface $operation) => $operation->canExecute($operationContext),
            ),
        );

        $canEdit = \in_array('save', $operationNames, true);

        $templates = [];
        $shadowed = false;
        $numTemplates = \count($logicalNamesChain);

        for ($i = 0; $i < $numTemplates; ++$i) {
            $logicalName = $logicalNamesChain[$i];
            $source = $this->loader->getSourceContext($logicalName);
            $templateInformation = $this->inspector->inspectTemplate($logicalName);
            $isComponent = $templateInformation->isComponent();

            $templateNameInformation = $this->getTemplateNameInformation($logicalName);

            $template = [
                ...$templateNameInformation,
                'path' => $source->getPath(),
                'code' => $source->getCode(),
                'is_origin' => $i === $numTemplates - 1,
                'is_component' => $isComponent,
                'relation' => [
                    'shadowed' => $shadowed,
                    'warning' => false,
                    'not_analyzable' => false,
                    'legacy_pair' => $this->isLegacyIdentifier($templateNameInformation['identifier']),
                ],
                'annotations' => $canEdit && 0 === $i
                    ? $this->getAnnotations($logicalName, $templateInformation->getError())
                    : [],
            ];

            // Analyze relation to see if the templates breaks the hierarchy
            if (null !== ($previous = $logicalNamesChain[$i + 1] ?? null)) {
                if ($templateInformation->hasValidInformation()) {
                    $breaksHierarchy = static fn (): bool => match ($isComponent) {
                        true => !$templateInformation->isUsing($previous),
                        false => $templateInformation->getExtends() !== $previous,
                    };

                    if ($breaksHierarchy()) {
                        $template['relation']['warning'] = true;
                        $shadowed = true;
                    }
                } else {
                    $template['relation']['not_analyzable'] = true;
                }
            }

            $templates[] = $template;
        }

        return $this->render('@Contao/backend/template_studio/editor/add_editor_tab.stream.html.twig', [
            'identifier' => $identifier,
            'templates' => $templates,
            'operations' => $operationNames,
            'can_edit' => $canEdit,
        ]);
    }

    /**
     * Resolve the given logical template name, then stream a tab with the
     * associated identifier.
     */
    #[Route(
        '/%contao.backend.route_prefix%/template-studio-follow',
        name: '_contao_template_studio_follow.stream',
        defaults: ['_scope' => 'backend', '_store_referrer' => false],
        methods: ['GET'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function follow(#[MapQueryParameter('name')] string $logicalName): Response
    {
        if (!($identifier = ContaoTwigUtil::getIdentifier($logicalName))) {
            return new Response('Could not retrieve template identifier.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->editorTab($identifier);
    }

    /**
     * Stream hierarchical block information for the given template and block name.
     */
    #[Route(
        '/%contao.backend.route_prefix%/template-studio-block-info',
        name: '_contao_template_studio_block_info.stream',
        defaults: ['_scope' => 'backend', '_store_referrer' => false],
        methods: ['GET'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function blockInfo(#[MapQueryParameter('block')] string $blockName, #[MapQueryParameter('name')] string $logicalName): Response
    {
        if (!$this->isAllowedIdentifier(ContaoTwigUtil::getIdentifier($logicalName))) {
            return new Response(
                'The given template cannot be inspected.',
                Response::HTTP_FORBIDDEN,
            );
        }

        $firstLogicalName = $this->loader->getFirst($logicalName);

        try {
            $blockHierarchy = $this->inspector->getBlockHierarchy($firstLogicalName, $blockName);
        } catch (InspectionException) {
            return new Response('Cannot retrieve requested block information.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Enrich data
        $blockHierarchy = array_values(
            array_map(
                fn (BlockInformation $info): array => [
                    'target' => false,
                    'shadowed' => false,
                    'warning' => false,
                    'info' => $info,
                    'template' => $this->getTemplateNameInformation($info->getTemplateName()),
                ],
                array_filter(
                    $blockHierarchy,
                    static fn (BlockInformation $hierarchy): bool => BlockType::transparent !== $hierarchy->getType(),
                ),
            ),
        );

        if ([] === $blockHierarchy) {
            return $this->render(
                '@Contao/backend/template_studio/info/failed_to_open_block.stream.html.twig',
                ['block' => $blockName],
            );
        }

        $numBlocks = \count($blockHierarchy);

        for ($i = 0; $i < $numBlocks; ++$i) {
            if ($blockHierarchy[$i]['info']->getTemplateName() === $logicalName) {
                $blockHierarchy[$i]['target'] = true;
                break;
            }
        }

        $shadowed = false;
        $lastOverwrite = null;

        for ($i = 0; $i < $numBlocks; ++$i) {
            if (BlockType::overwrite === $blockHierarchy[$i]['info']->getType()) {
                $shadowed = true;

                if (null !== $lastOverwrite) {
                    $blockHierarchy[$lastOverwrite]['warning'] = true;
                    $blockHierarchy[$i]['shadowed'] = true;
                }

                $lastOverwrite = $i;

                continue;
            }

            $blockHierarchy[$i]['shadowed'] = $shadowed;

            if (null !== $lastOverwrite && BlockType::origin === $blockHierarchy[$i]['info']->getType() && !$blockHierarchy[$i]['info']->isPrototype()) {
                $blockHierarchy[$lastOverwrite]['warning'] = true;
            }
        }

        return $this->render('@Contao/backend/template_studio/info/block_info.stream.html.twig', [
            'hierarchy' => $blockHierarchy,
            'block' => $blockName,
            'target_template' => $this->getTemplateNameInformation($logicalName),
        ]);
    }

    /**
     * Stream data for code annotations (such as autocompletion data) for the
     * given template.
     */
    #[Route(
        '/%contao.backend.route_prefix%/template-studio-annotations-data',
        name: '_contao_template_studio_annotations_data.stream',
        defaults: ['_scope' => 'backend', '_store_referrer' => false],
        methods: ['GET'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function annotationsData(#[MapQueryParameter] string $identifier): Response
    {
        if (!$this->isAllowedIdentifier($identifier)) {
            return new Response(
                'No autocompletion data can be generated for the given template.',
                Response::HTTP_FORBIDDEN,
            );
        }

        $logicalName = $this->loader->getFirst($identifier);
        $error = $this->inspector->inspectTemplate($logicalName)->getError();

        return $this->render('@Contao/backend/template_studio/editor/annotations.stream.html.twig', [
            'identifier' => $identifier,
            'annotations' => $this->getAnnotations($logicalName, $error),
        ]);
    }

    /**
     * Execute an operation and stream the result.
     */
    #[Route(
        '/%contao.backend.route_prefix%/template-studio/resource/{identifier}',
        name: '_contao_template_studio_operation.stream',
        requirements: ['identifier' => '.+'],
        defaults: ['_scope' => 'backend', '_token_check' => false, '_store_referrer' => false],
        methods: ['POST'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function operation(Request $request, string $identifier, #[MapQueryParameter('operation')] string $operationName): Response
    {
        if (null === ($operation = ($this->operations[$operationName] ?? null)) || !$this->isAllowedIdentifier($identifier)) {
            return new Response(
                'Cannot execute given operation for the given template identifier.',
                Response::HTTP_FORBIDDEN,
            );
        }

        $operationContext = $this->getOperationContext($identifier);

        $result = $operation->execute($request, $operationContext);

        // Operations can either stream their own intermediary steps, a custom result or
        // nothing at all - in which case we stream a default result.
        $request->setRequestFormat('turbo_stream');

        return $result ?? $this->render('@Contao/backend/template_studio/operation/default_result.stream.html.twig', [
            'operation' => $operationName,
            'context' => $operationContext,
        ]);
    }

    protected function getOperationContext(string $identifier): OperationContext
    {
        return $this->operationContextFactory->create(
            $identifier,
            ContaoTwigUtil::getExtension($this->loader->getFirst($identifier)),
            $this->getThemeContext(),
        );
    }

    private function generateTree(): array
    {
        $userNamespace = '@Contao_Global';

        if (null !== ($themeSlug = $this->getThemeContext())) {
            $userNamespace = $this->themeNamespace->getFromSlug($themeSlug);
        }

        $prefixTree = [];
        $legacyNodes = [];

        foreach ($this->getFinder() as $identifier => $extension) {
            $parts = explode('/', $identifier);
            $node = &$prefixTree;

            foreach ($parts as $part) {
                /** @phpstan-ignore isset.offset */
                if (!isset($node[$part])) {
                    $node[$part] = [];
                }

                $node = &$node[$part];
            }

            $hasUserTemplate = $this->loader->exists("$userNamespace/$identifier.$extension");

            $leaf = new class($identifier, $hasUserTemplate) {
                public function __construct(
                    public readonly string $identifier,
                    public readonly bool $hasUserTemplate,
                ) {
                }
            };

            // Group legacy templates under their own key
            if ($this->isLegacyIdentifier($identifier)) {
                $legacyNodes[$identifier] = [$leaf];
                continue;
            }

            $node = [...$node, $leaf];
        }

        $sortRecursive = static function (&$node) use (&$sortRecursive): void {
            if (!\is_array($node)) {
                return;
            }

            ksort($node);

            foreach ($node as &$child) {
                $sortRecursive($child);
            }
        };

        $sortRecursive($prefixTree);
        ksort($legacyNodes);

        return array_filter([
            // Apply opinionated ordering by explicitly placing keys of the prefix tree
            // before merging it
            'content_element' => [],
            'frontend_module' => [],
            'component' => [],
            ...$prefixTree,
            // Append legacy nodes to the end under a virtual "legacy" key
            '(legacy)' => $legacyNodes,
        ]);
    }

    private function isLegacyIdentifier(string $identifier): bool
    {
        return !str_contains($identifier, '/') && $this->loader->exists("@Contao/$identifier.html5");
    }

    /**
     * @return array<string, string>
     */
    private function getTemplateNameInformation(string $logicalName): array
    {
        [$namespace, $shortName] = ContaoTwigUtil::parseContaoName($logicalName);

        return [
            'name' => $logicalName,
            'short_name' => $shortName ?? '?',
            'namespace' => $namespace ?? '?',
            'identifier' => ContaoTwigUtil::getIdentifier($shortName) ?: '?',
            'extension' => ContaoTwigUtil::getExtension($shortName) ?: '?',
        ];
    }

    private function getAnnotations(string $logicalName, Error|null $error): array
    {
        $data = [
            'autocomplete' => $this->autocomplete->getCompletions($logicalName),
        ];

        $getRootError = static function (\Throwable $e) use (&$getRootError): \Throwable {
            return (!($previous = $e->getPrevious())) ? $e : $getRootError($previous);
        };

        if ($error instanceof SyntaxError) {
            $rootError = $getRootError($error);

            $data['error'] = [
                'line' => $rootError instanceof SyntaxError ? $rootError->getLine() : 1,
                'message' => "Syntax Error\n\n{$rootError->getMessage()}",
            ];
        } elseif ($error instanceof LoaderError) {
            $data['error'] = [
                'line' => 1,
                'message' => "Loader Error\n\n{$error->getMessage()}",
            ];
        } elseif ($error instanceof RuntimeError) {
            $data['error'] = [
                'type' => 'warning',
                'line' => $error->getLine(),
                'message' => "Runtime Error\n\n{$error->getMessage()}",
            ];
        }

        return $data;
    }

    private function isAllowedIdentifier(string $identifier): bool
    {
        foreach ($this->getFinder() as $allowedIdentifier => $_) {
            if ($allowedIdentifier === $identifier) {
                return true;
            }
        }

        return false;
    }

    private function getFinder(): Finder
    {
        $suppressedPrefixes = ['backend', 'frontend_preview', 'web_debug_toolbar'];

        // TODO: In Contao 6, do not add theme paths in the ContaoFilesystemLoader to
        // begin with and remove this logic to suppress them (see #7027).
        foreach (array_keys($this->getAvailableThemes()) as $slug) {
            $suppressedPrefixes[] = $this->themeNamespace->getPath($slug);
        }

        $regex = \sprintf('#^(%s)/#', implode('|', array_map(
            static fn (string $path): string => preg_quote($path, '#'),
            $suppressedPrefixes,
        )));

        return $this->finder
            ->create()
            ->identifierRegex($regex, false)
        ;
    }

    private function setThemeContext(string|null $slug): void
    {
        $this->getBackendSessionBag()?->set('template_studio_theme_slug', $slug);
    }

    private function getThemeContext(): string|null
    {
        return $this->getBackendSessionBag()?->get('template_studio_theme_slug');
    }

    /**
     * @return array<string, string>
     */
    private function getAvailableThemes(): array
    {
        // Filter out themes that either have no valid template path set or where the
        // template path is outside the template directory and remove duplicates.
        $themes = $this->connection->fetchAllKeyValue("
            SELECT SUBSTR(templates, 11), name
            FROM tl_theme
            WHERE templates != ''
                AND templates != 'templates'
                AND templates NOT LIKE '%..%'
            GROUP BY templates, name
            ORDER BY name
        ");

        return array_combine(
            array_map(
                fn (string $path): string => $this->themeNamespace->generateSlug($path),
                array_keys($themes),
            ),
            array_values($themes),
        );
    }
}
