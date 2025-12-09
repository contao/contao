<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\BackendTemplateStudioController;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Finder\Finder;
use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\CoreBundle\Twig\Inspector\Inspector;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Contao\CoreBundle\Twig\Studio\Autocomplete;
use Contao\CoreBundle\Twig\Studio\EnvironmentInformation;
use Contao\CoreBundle\Twig\Studio\Operation\AbstractOperation;
use Contao\CoreBundle\Twig\Studio\Operation\OperationContext;
use Contao\CoreBundle\Twig\Studio\Operation\OperationContextFactory;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class BackendTemplateStudioControllerTest extends TestCase
{
    #[DataProvider('provideControllerActionsThatValidateIdentifiers')]
    public function testInvalidIdentifierIsDenied(string $action, array $parameters, string|null $streamError = null, Request|null $request = null): void
    {
        $request ??= new Request();
        $request->headers->set('Accept', 'text/vnd.turbo-stream.html');

        $twig = null;

        if (null !== $streamError) {
            $twig = $this->createMock(Environment::class);
            $twig
                ->expects($this->once())
                ->method('render')
                ->with($streamError, $this->anything())
                ->willReturn('')
            ;
        }

        $controller = $this->getBackendTemplatedStudioController($twig, $request);
        $response = $controller->$action(...$parameters);

        if (null === $streamError) {
            $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        }
    }

    public static function provideControllerActionsThatValidateIdentifiers(): iterable
    {
        $invalidIdentifiers = [
            'invalid_identifier',
            'backend/x',
            'theme/foo',
            'frontend_preview',
            'web_debug_toolbar',
        ];

        foreach ($invalidIdentifiers as $invalidIdentifier) {
            yield "editor tab with identifier '$invalidIdentifier'" => [
                'editorTab',
                [$invalidIdentifier],
                '@Contao/backend/template_studio/editor/failed_to_open_tab.stream.html.twig',
            ];

            yield "follow with identifier '$invalidIdentifier'" => [
                'follow',
                ["@Contao/$invalidIdentifier.html.twig"],
                '@Contao/backend/template_studio/editor/failed_to_open_tab.stream.html.twig',
            ];

            yield "block info with identifier '$invalidIdentifier'" => [
                'blockInfo',
                ['foo_block', "@Contao/$invalidIdentifier.html.twig"],
            ];

            yield "annotations with identifier '$invalidIdentifier'" => [
                'annotationsData',
                ['invalid_identifier'],
            ];

            yield "operation with identifier '$invalidIdentifier'" => [
                'operation',
                [$request = new Request(), $invalidIdentifier, 'foo_operation'],
                null,
                $request,
            ];
        }
    }

    /**
     * @param (Environment&MockObject)|null $twig
     */
    private function getBackendTemplatedStudioController(Environment|null $twig = null, Request|null $request = null): BackendTemplateStudioController
    {
        $loader = $this->createMock(ContaoFilesystemLoader::class);
        $loader
            ->method('getInheritanceChains')
            ->willReturn([
                'foo' => [
                    '@Contao/foo.html.twig' => '/path/to/foo.html.twig',
                ],
                'backend/x' => [
                    '@Contao/backend/x.html.twig' => '/path/to/backend/x.html.twig',
                ],
                'my/theme/foo' => [
                    '@Contao/my/theme/foo.html.twig' => '/path/to/my/theme/foo.html.twig',
                ],
            ])
        ;

        $finder = new Finder(
            $loader,
            new ThemeNamespace(),
            $this->createMock(TranslatorInterface::class),
        );

        $finderFactory = $this->createMock(FinderFactory::class);
        $finderFactory
            ->method('create')
            ->willReturn($finder)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAllAssociative')
            ->willReturnCallback(
                function (string $query) {
                    $this->assertMatchesRegularExpression(
                        '/^\w*SELECT SUBSTR(templates, 11), name\w+FROM tl_theme/',
                        $query,
                    );

                    return [
                        'my/theme' => 'my theme',
                    ];
                },
            )
        ;

        $fooOperation = new class() extends AbstractOperation {
            public function canExecute(OperationContext $context): bool
            {
                return false;
            }

            public function execute(Request $request, OperationContext $context): Response|null
            {
                throw new \RuntimeException('not implemented');
            }
        };

        $controller = new BackendTemplateStudioController(
            $loader,
            $finderFactory,
            $this->createMock(Inspector::class),
            $this->createMock(ThemeNamespace::class),
            $this->createMock(OperationContextFactory::class),
            $this->createMock(Autocomplete::class),
            $this->createMock(EnvironmentInformation::class),
            $connection,
            ['foo_operation' => $fooOperation],
        );

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->method('isGranted')
            ->willReturn(true)
        ;

        $requestStack = new RequestStack([$request ?? new Request()]);

        $container = $this->getContainerWithContaoConfiguration($this->getTempDir());
        $container->set('security.token_storage', $this->createMock(TokenStorageInterface::class));
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));
        $container->set('security.authorization_checker', $authorizationChecker);
        $container->set('database_connection', $connection);
        $container->set('twig', $twig ?? $this->createMock(Environment::class));
        $container->set('request_stack', $requestStack);

        $controller->setContainer($container);

        return $controller;
    }
}
