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
use Contao\CoreBundle\Twig\Studio\Operation\OperationInterface;
use Contao\CoreBundle\Twig\Studio\Operation\TemplateContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @experimental
 */
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
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('@Contao/backend/template_studio/index.html.twig', [
            'title' => 'Template Studio',
            'headline' => 'Template Studio',
            'tree' => $this->generateTree(),
        ]);
    }

    /**
     * Stream a prefix tree of template identifiers.
     */
    #[Route(
        '/%contao.backend.route_prefix%/template-studio-tree',
        name: '_contao_template_studio_tree.stream',
        defaults: ['_scope' => 'backend'],
        methods: ['GET'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function tree(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('@Contao/backend/template_studio/tree/tree.stream.html.twig', [
            'tree' => $this->generateTree(),
        ]);
    }

    /**
     * Stream an editor tab for the given identifier.
     */
    #[Route(
        '/%contao.backend.route_prefix%/template-studio/resource/{identifier}',
        name: '_contao_template_studio_editor_tab.stream',
        requirements: ['identifier' => '.+'],
        defaults: ['_scope' => 'backend'],
        methods: ['GET'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function editorTab(string $identifier): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isAllowedIdentifier($identifier)) {
            return new Response('The given template identifier cannot be opened in an editor tab.', Response::HTTP_FORBIDDEN);
        }

        $chains = $this->loader->getInheritanceChains()[$identifier];

        $operationContext = new TemplateContext(
            $identifier,
            ContaoTwigUtil::getExtension($chains[array_key_first($chains)]),
        );

        $operationNames = array_keys(
            array_filter(
                $this->operations,
                static fn (OperationInterface $operation) => $operation->canExecute($operationContext),
            ),
        );

        return $this->render('@Contao/backend/template_studio/editor/add_editor_tab.stream.html.twig', [
            'identifier' => $identifier,
            'templates' => array_map(
                function (string $logicalName): array {
                    $source = $this->loader->getSourceContext($logicalName);

                    return [
                        ...$this->getTemplateNameInformation($logicalName),
                        'path' => $source->getPath(),
                        'code' => $source->getCode(),
                    ];
                },
                $chains,
            ),
            'operations' => $operationNames,
            'can_edit' => \in_array('save', $operationNames, true),
        ]);
    }

    /**
     * Resolve the given logical template name, then stream a tab with the
     * associated identifier.
     */
    #[Route(
        '/%contao.backend.route_prefix%/template-studio-follow',
        name: '_contao_template_studio_follow.stream',
        defaults: ['_scope' => 'backend'],
        methods: ['GET'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function follow(#[MapQueryParameter('name')] string $logicalName): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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
        defaults: ['_scope' => 'backend'],
        methods: ['GET'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function blockInfo(#[MapQueryParameter('block')] string $blockName, #[MapQueryParameter('name')] string $logicalName): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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
     * Execute an operation and stream the result.
     */
    #[Route(
        '/%contao.backend.route_prefix%/template-studio/resource/{identifier}',
        name: '_contao_template_studio_operation.stream',
        requirements: ['identifier' => '.+'],
        defaults: ['_scope' => 'backend', '_token_check' => false],
        methods: ['POST'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function operation(Request $request, string $identifier, #[MapQueryParameter('operation')] string $operationName): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (null === ($operation = ($this->operations[$operationName] ?? null)) || !$this->isAllowedIdentifier($identifier)) {
            return new Response(
                'Cannot execute given operation for the given template identifier.',
                Response::HTTP_FORBIDDEN,
            );
        }

        $operationContext = new TemplateContext(
            $identifier,
            ContaoTwigUtil::getExtension($this->loader->getFirst($identifier)),
        );

        $result = $operation->execute($request, $operationContext);

        // Operations can either stream their own intermediary steps, a custom result or
        // nothing at all - in which case we stream a default result.
        $request->setRequestFormat('turbo_stream');

        return $result ?? $this->render('@Contao/backend/template_studio/operation/default_result.stream.html.twig', [
            'operation' => $operationName,
            'context' => $operationContext,
        ]);
    }

    private function generateTree(): array
    {
        $prefixTree = [];

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

            $hasUserTemplate = $this->loader->exists("@Contao_Global/$identifier.$extension");

            $leaf = new class($identifier, $hasUserTemplate) {
                public function __construct(
                    public readonly string $identifier,
                    public readonly bool $hasUserTemplate,
                ) {
                }
            };

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

        // Apply opinionated ordering
        return ['content_element' => [], 'frontend_module' => [], 'component' => [], ...$prefixTree];
    }

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
        return $this->finder
            ->create()
            ->identifierRegex('%^backend/%', false)
        ;
    }
}
