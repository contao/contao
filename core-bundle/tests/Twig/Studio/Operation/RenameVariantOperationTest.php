<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Twig\Studio\Operation;

use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Studio\CacheInvalidator;
use Contao\CoreBundle\Twig\Studio\Operation\AbstractRenameVariantOperation;
use Contao\CoreBundle\Twig\Studio\Operation\OperationContext;
use Contao\CoreBundle\Twig\Studio\TemplateSkeletonFactory;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class RenameVariantOperationTest extends AbstractOperationTestCase
{
    #[DataProvider('provideContextsAndIfAllowedToExecute')]
    public function testCanExecute(OperationContext $context, bool $canExecute): void
    {
        $this->assertSame(
            $canExecute,
            $this->getRenameVariantOperation()->canExecute($context),
        );
    }

    public static function provideContextsAndIfAllowedToExecute(): iterable
    {
        yield 'arbitrary identifier' => [
            static::getOperationContext('bar/foo'),
            false,
        ];

        yield 'identifier matching the prefix' => [
            static::getOperationContext('prefix/foo'),
            false,
        ];

        yield 'matching variant identifier' => [
            static::getOperationContext('prefix/foo/my_variant'),
            true,
        ];

        yield 'matching nested variant identifier' => [
            static::getOperationContext('prefix/foo/bar/my_variant'),
            true,
        ];

        yield 'arbitrary identifier in theme context' => [
            static::getOperationContext('bar/foo', 'theme'),
            false,
        ];

        yield 'identifier matching the prefix in theme context' => [
            static::getOperationContext('prefix/foo', 'theme'),
            false,
        ];

        yield 'matching variant identifier in theme context' => [
            static::getOperationContext('prefix/foo/my_variant', 'theme'),
            false,
        ];
    }

    public function testStreamDialogWhenRenamingVariantTemplate(): void
    {
        $loader = $this->mockContaoFilesystemLoader();
        $loader
            ->method('exists')
            ->willReturnCallback(
                static fn (string $name) => \in_array($name, [
                    '@Contao/prefix/foo.html.twig',
                    '@Contao/prefix/foo/new_variant.html.twig',
                    '@Contao/prefix/foo/new_variant2.html.twig',
                ], true),
            )
        ;

        $loader
            ->method('getInheritanceChains')
            ->willReturn([
                'bar' => [
                    '@Contao/bar.html.twig' => '/path/to/bar.html.twig',
                ],
                'prefix/foo' => [
                    '@Contao/prefix/foo.html.twig' => '/path/to/prefix/foo.html.twig',
                ],
                'prefix/foo/new_variant' => [
                    '@Contao/prefix/foo/new_variant.html.twig' => '/path/to/prefix/foo/new_variant.html.twig',
                ],
                'prefix/foo/new_variant2' => [
                    '@Contao/prefix/foo/new_variant2.html.twig' => '/path/to/prefix/foo/new_variant2.html.twig',
                ],
            ])
        ;

        $storage = $this->mockUserTemplatesStorage();
        $storage
            ->expects($this->never())
            ->method('write')
        ;

        $twig = $this->mockTwigEnvironment();
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/template_studio/operation/create_or_rename_variant.stream.html.twig',
                [
                    'operation' => 'rename_prefix_variant',
                    'operation_type' => 'rename',
                    'identifier' => 'prefix/foo',
                    'extension' => 'html.twig',
                    'suggested_identifier_fragment' => 'new_variant',
                    'allowed_identifier_fragment_pattern' => '^(?!(new_variant|new_variant2)$).*',
                ],
            )
            ->willReturn('create_or_rename_variant.stream')
        ;

        $operation = $this->getRenameVariantOperation($loader, $storage, $twig);

        $response = $operation->execute(
            new Request(),
            static::getOperationContext('prefix/foo/new_variant'),
        );

        $this->assertSame('create_or_rename_variant.stream', $response->getContent());
    }

    public function testFailToCreateVariantTemplateBecauseNewNameAlreadyExists(): void
    {
        $storage = $this->mockUserTemplatesStorage(['prefix/foo.html.twig', 'prefix/foo/my_variant.html.twig', 'prefix/foo/my_new_variant.html.twig']);
        $storage
            ->expects($this->never())
            ->method('write')
        ;

        $twig = $this->mockTwigEnvironment();
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/template_studio/operation/default_result.stream.html.twig',
                $this->anything(),
            )
            ->willReturn('error.stream')
        ;

        $operation = $this->getRenameVariantOperation(storage: $storage, twig: $twig);

        $response = $operation->execute(
            new Request(request: ['identifier_fragment' => 'my_new_variant']),
            static::getOperationContext('prefix/foo/my_variant'),
        );

        $this->assertSame('error.stream', $response->getContent());
    }

    public function testRenameVariantTemplate(): void
    {
        $loader = $this->mockContaoFilesystemLoader();
        $loader
            ->expects($this->once())
            ->method('warmUp')
            ->with(true)
        ;

        $storage = $this->mockUserTemplatesStorage(['prefix/foo.html.twig', 'prefix/foo/my_variant.html.twig']);
        $storage
            ->expects($this->once())
            ->method('move')
            ->with('prefix/foo/my_variant.html.twig', 'prefix/foo/my_new_variant.html.twig')
        ;

        $twig = $this->mockTwigEnvironment();
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/template_studio/operation/rename_variant_result.stream.html.twig',
                ['old_identifier' => 'prefix/foo/my_variant', 'new_identifier' => 'prefix/foo/my_new_variant'],
            )
            ->willReturn('rename_variant_result.stream')
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'some_table',
                ['some_template_field' => 'prefix/foo/my_new_variant'],
                ['some_template_field' => 'prefix/foo/my_variant'],
            )
        ;

        $cacheInvalidator = $this->mockCacheInvalidator();
        $cacheInvalidator
            ->expects($this->once())
            ->method('invalidateCache')
            ->with('prefix/foo/my_variant', null)
        ;

        $operation = $this->getRenameVariantOperation(
            $loader,
            $storage,
            $twig,
            $this->mockTemplateSkeletonFactory('@Contao/prefix/foo.html.twig'),
            $connection,
            $cacheInvalidator,
        );

        $response = $operation->execute(
            new Request(request: ['identifier_fragment' => 'my_new_variant']),
            static::getOperationContext('prefix/foo/my_variant'),
        );

        $this->assertSame('rename_variant_result.stream', $response->getContent());
    }

    private function getRenameVariantOperation(ContaoFilesystemLoader|null $loader = null, VirtualFilesystemInterface|null $storage = null, Environment|null $twig = null, TemplateSkeletonFactory|null $skeletonFactory = null, Connection|null $connection = null, CacheInvalidator|null $cacheInvalidator = null): AbstractRenameVariantOperation
    {
        $operation = new class() extends AbstractRenameVariantOperation {
            protected function getPrefix(): string
            {
                return 'prefix';
            }

            protected function getDatabaseReferencesThatShouldBeMigrated(): array
            {
                return ['some_table.some_template_field'];
            }
        };

        $container = $this->getContainer($loader, $storage, $twig, $skeletonFactory, $cacheInvalidator);
        $container->set('database_connection', $connection);

        $operation->setContainer($container);
        $operation->setName('rename_prefix_variant');

        return $operation;
    }
}
