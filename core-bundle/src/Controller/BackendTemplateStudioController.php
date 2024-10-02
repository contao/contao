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
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Source;

class BackendTemplateStudioController extends AbstractBackendController
{
    public function __construct(
        private readonly ContaoFilesystemLoader $loader,
        private readonly FinderFactory $finder,
    ) {
    }

    #[Route(
        '/contao/template-studio',
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
        ]);
    }

    /**
     * Render an editor tab for a given identifier.
     */
    #[Route(
        '/_contao/template-studio/resource/{identifier}',
        name: '_contao_template_studio_editor_tab',
        requirements: ['identifier' => '.+'],
        defaults: ['_scope' => 'backend'],
        methods: ['GET'],
    )]
    public function editorTab(string $identifier): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!($this->loader->getInheritanceChains()[$identifier] ?? false)) {
            return new Response(\sprintf('Invalid identifier "%s"', $identifier), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $sources = array_map(
            fn (string $name): Source => $this->loader->getSourceContext($name),
            $this->loader->getInheritanceChains()[$identifier] ?? [],
        );

        return $this->render('@Contao/backend/template_studio/editor/add_editor_tab.stream.html.twig', [
            'identifier' => $identifier,
            'templates' => array_map(
                function (Source $source): array {
                    $templateNameInformation = $this->getTemplateNameInformation($source->getName());

                    return [
                        ...$templateNameInformation,
                        'path' => $source->getPath(),
                        'code' => $source->getCode(),
                    ];
                },
                $sources,
            ),
            'can_edit' => false,
        ]);
    }

    /**
     * Build a prefix tree of template identifiers.
     */
    #[Route(
        '/_contao/template-studio-tree',
        name: '_contao_template_studio_tree',
        defaults: ['_scope' => 'backend'],
        methods: ['GET'],
    )]
    public function tree(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $prefixTree = [];

        foreach ($this->finder->create() as $identifier => $extension) {
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

        // Don't show backend templates
        unset($prefixTree['backend']);

        // Apply opinionated ordering
        $prefixTree = ['content_element' => [], 'frontend_module' => [], 'component' => [], ...$prefixTree];

        return $this->render('@Contao/backend/template_studio/tree.html.twig', [
            'tree' => $prefixTree,
        ]);
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
}
