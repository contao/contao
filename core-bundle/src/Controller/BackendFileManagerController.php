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

use Contao\BackendUser;
use Contao\CoreBundle\Filesystem\DirectoryFilterVirtualFilesystem;
use Contao\CoreBundle\Filesystem\FileDownloadHelper;
use Contao\CoreBundle\Filesystem\FileManager\Operation\ElementsOperationContext;
use Contao\CoreBundle\Filesystem\FileManager\Operation\ElementsOperationInterface;
use Contao\CoreBundle\Filesystem\FileManager\Operation\ViewOperationContext;
use Contao\CoreBundle\Filesystem\FileManager\Operation\ViewOperationInterface;
use Contao\CoreBundle\Filesystem\PermissionCheckingVirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Image\Studio\Studio;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @experimental
 */
#[IsGranted('ROLE_ADMIN', message: 'Access restricted to administrators.')] // todo remove once every method is secured
class BackendFileManagerController extends AbstractBackendController
{
    private VirtualFilesystemInterface $storage;

    private string $initialViewPath = '';

    /**
     * @var array<string, ElementsOperationInterface>
     */
    private array $elementsOperations;

    /**
     * @var array<string, ViewOperationInterface>
     */
    private array $viewOperations;

    public function __construct(
        VirtualFilesystem $filesStorage,
        Security $security,
        private readonly Studio $studio,
        private readonly FileDownloadHelper $downloadHelper,
        iterable $elementsOperations,
        iterable $viewOperations,
    ) {
        /** @var BackendUser $user */
        $user = $security->getUser();

        if ($user->isAdmin) {
            $this->storage = $filesStorage;
        } else {
            $allowedPaths = array_map(
                static fn (string $rootRelativePath): string => Path::makeRelative($rootRelativePath, 'files'),
                $user->filemounts,
            );

            $this->storage = new DirectoryFilterVirtualFilesystem(
                new PermissionCheckingVirtualFilesystem($filesStorage, $security),
                $allowedPaths,
            );

            $this->initialViewPath = $this->storage->getFirstNonVirtualDirectory() ?? '';
        }

        // todo: there should not be a prefix "elements_" or "view_"
        $this->elementsOperations = $elementsOperations instanceof \Traversable ? iterator_to_array($elementsOperations) : $elementsOperations;
        $this->viewOperations = $viewOperations instanceof \Traversable ? iterator_to_array($viewOperations) : $viewOperations;
    }

    #[Route(
        '/%contao.backend.route_prefix%/file-manager',
        name: 'contao_file_manager',
        defaults: ['_scope' => 'backend'],
        methods: ['GET'],
    )]
    public function __invoke(): Response
    {
        return $this->render('@Contao/backend/file_manager/index.html.twig', [
            'title' => 'File Manager',
            'headline' => 'File Manager',
            ...$this->generateListingData($this->initialViewPath),
            ...$this->generateTreeData($this->initialViewPath),
        ]);
    }

    /**
     * Stream the filesystem tree.
     */
    #[Route(
        '/%contao.backend.route_prefix%/file-manager-navigation/{path}',
        name: '_contao_file_manager_navigation.stream',
        requirements: ['path' => '.*'],
        defaults: ['_scope' => 'backend', '_store_referrer' => false],
        methods: ['GET'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function navigation(string $path = ''): Response
    {
        return $this->render(
            '@Contao/backend/file_manager/navigation/navigation.stream.html.twig',
            $this->generateTreeData($path),
        );
    }

    /**
     * Stream filesystem content view for the given path.
     */
    #[Route(
        '/%contao.backend.route_prefix%/file-manager/resource/{path}',
        name: '_contao_file_manager_list_content.stream',
        requirements: ['path' => '.*'],
        defaults: ['_scope' => 'backend', '_store_referrer' => false],
        methods: ['GET'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function listContent(string $path): Response
    {
        if ('' !== $path && !$this->storage->directoryExists($path)) {
            return new Response(
                'The given path cannot be opened.',
                Response::HTTP_FORBIDDEN,
            );
        }

        return $this->render(
            '@Contao/backend/file_manager/content/display_content.stream.html.twig',
            $this->generateListingData($path),
        );
    }

    /**
     * Displays the available elements operations for a given list of paths.
     */
    #[Route(
        '/%contao.backend.route_prefix%/file-manager-elements-operations',
        name: '_contao_file_manager_elements_operations.stream',
        defaults: ['_scope' => 'backend', '_store_referrer' => false],
        methods: ['GET'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function listElementsOperations(#[MapQueryParameter('paths')] array $paths): Response
    {
        $operationContext = new ElementsOperationContext($paths, $this->storage);

        $operations = array_filter(
            $this->elementsOperations,
            static fn (ElementsOperationInterface $operation): bool => $operation->canExecute($operationContext),
        );

        return $this->render('@Contao/backend/file_manager/content/elements_operations.stream.html.twig', [
            'operations' => array_keys($operations),
            'paths' => $paths,
        ]);
    }

    /**
     * Execute an operation and stream the result.
     */
    #[Route(
        '/%contao.backend.route_prefix%/file-manager/elements-operation/{operation}',
        name: '_contao_file_manager_elements_operation.stream',
        requirements: ['operation' => '.+'],
        defaults: ['_scope' => 'backend', '_token_check' => false, '_store_referrer' => false],
        methods: ['POST'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function elementsOperation(Request $request, string $operation, #[MapQueryParameter('paths')] array $paths): Response
    {
        if (null === ($operationInstance = ($this->elementsOperations[$operation] ?? null))) {
            return new Response(
                'Cannot execute given elements operation.',
                Response::HTTP_FORBIDDEN,
            );
        }

        $operationContext = new ElementsOperationContext($paths, $this->storage);

        // Check if operation can still be executed
        if(!$operationInstance->canExecute($operationContext)) {
            return $this->render(
                '@Contao/backend/file_manager/operation/failed_to_execute_operation.stream.html.twig',
                ['operation' => $operation],
            );
        }

        $result = $operationInstance->execute($request, $operationContext);

        // Operations can either stream their own intermediary steps, a custom result or
        // nothing at all - in which case we return a 204 response.
        $request->setRequestFormat('turbo_stream');

        return $result ?? new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Execute an operation and stream the result.
     */
    #[Route(
        '/%contao.backend.route_prefix%/file-manager/view-operation/{operation}',
        name: '_contao_file_manager_view_operation.stream',
        requirements: ['operation' => '.+'],
        defaults: ['_scope' => 'backend', '_token_check' => false, '_store_referrer' => false],
        methods: ['POST'],
        condition: "'text/vnd.turbo-stream.html' in request.getAcceptableContentTypes()",
    )]
    public function viewOperation(Request $request, string $operation, #[MapQueryParameter('path')] string $path): Response
    {
        if (null === ($operationInstance = ($this->viewOperations[$operation] ?? null))) {
            return new Response(
                'Cannot execute given view operation.',
                Response::HTTP_FORBIDDEN,
            );
        }

        $operationContext = new ViewOperationContext($path, $this->storage);

        // Check if operation can still be executed
        if(!$operationInstance->canExecute($operationContext)) {
            return $this->render(
                '@Contao/backend/file_manager/operation/failed_to_execute_operation.stream.html.twig',
                ['operation' => $operation],
            );
        }

        $result = $operationInstance->execute($request, $operationContext);

        // Operations can either stream their own intermediary steps, a custom result or
        // nothing at all - in which case we return a 204 response.
        $request->setRequestFormat('turbo_stream');

        return $result ?? new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route(
        '/%contao.backend.route_prefix%/file-manager/download',
        name: '_contao_file_manager_download',
        requirements: ['operation' => '.+'],
        defaults: ['_scope' => 'backend', '_token_check' => false, '_store_referrer' => false],
        methods: ['GET'],
    )]
    public function download(Request $request): Response
    {
        // Stream/download the file that was included in the signed request url.
        return $this->downloadHelper->handle($request, $this->storage);
    }

    private function generateTreeData(string $path): array
    {
        $items = $this->storage
            ->listContents('', true)
            ->directories()
            ->sort()
        ;

        $prefixTree = [];

        foreach ($items as $item) {
            $parts = explode('/', $item->getPath());
            $node = &$prefixTree;

            foreach ($parts as $part) {
                /** @phpstan-ignore-next-line */
                if (!isset($node[$part])) {
                    $node[$part] = [];
                }

                $node = &$node[$part];
            }
        }

        return [
            'tree' => $prefixTree,
            'path' => $path,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateListingData(string $path): array
    {
        $figureBuilder = $this->studio
            ->createFigureBuilder()
            ->setSize([150, 110])
            ->disableMetadata()
        ;

        $elements = [];

        foreach ($this->storage->listContents($path)->sort() as $item) {
            $preview = null;

            if ($item->isImage()) {
                $preview = $figureBuilder->fromFilesystemItem($item)->buildIfResourceExists();
            }

            $elements[] = [
                'filesystem_item' => $item,
                'preview' => $preview,
            ];
        }

        $operationContext = new ViewOperationContext($path, $this->storage);

        $operations = array_filter(
            $this->viewOperations,
            static fn (ViewOperationInterface $operation): bool => $operation->canExecute($operationContext),
        );

        return [
            'elements' => $elements,
            'view_operations' => array_keys($operations),
            'path' => $path,
        ];
    }
}
