<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Twig\Studio\Operation;

use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Studio\Operation\AbstractCreateVariantOperation;
use Contao\CoreBundle\Twig\Studio\Operation\OperationContext;
use Contao\CoreBundle\Twig\Studio\TemplateSkeletonFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class CreateVariantOperationTest extends AbstractOperationTestCase
{
    #[DataProvider('provideContextsAndIfAllowedToExecute')]
    public function testCanExecute(OperationContext $context, bool $canExecute): void
    {
        $this->assertSame(
            $canExecute,
            $this->getCreateVariantOperation()->canExecute($context),
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
            true,
        ];

        yield 'nested identifier matching the prefix' => [
            static::getOperationContext('prefix/foo/baz'),
            false,
        ];

        yield 'arbitrary identifier in theme context' => [
            static::getOperationContext('bar/foo', 'theme'),
            false,
        ];

        yield 'identifier matching the prefix in theme context' => [
            static::getOperationContext('prefix/foo', 'theme'),
            false,
        ];
    }

    public function testStreamDialogWhenCreatingVariantTemplate(): void
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
                    'operation' => 'create_prefix_variant',
                    'operation_type' => 'create',
                    'identifier' => 'prefix/foo',
                    'extension' => 'html.twig',
                    'suggested_identifier_fragment' => 'new_variant3',
                    'allowed_identifier_fragment_pattern' => '^(?!(new_variant|new_variant2)$).*',
                ],
            )
            ->willReturn('create_or_rename_variant.stream')
        ;

        $operation = $this->getCreateVariantOperation($loader, $storage, $twig);

        $response = $operation->execute(
            new Request(),
            $this->getOperationContext('prefix/foo'),
        );

        $this->assertSame('create_or_rename_variant.stream', $response->getContent());
    }

    public function testFailToCreateVariantTemplateBecauseItAlreadyExists(): void
    {
        $storage = $this->mockUserTemplatesStorage(['prefix/foo.html.twig', 'prefix/foo/my_variant.html.twig']);
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

        $operation = $this->getCreateVariantOperation(storage: $storage, twig: $twig);

        $response = $operation->execute(
            new Request(request: ['identifier_fragment' => 'my_variant']),
            $this->getOperationContext('prefix/foo'),
        );

        $this->assertSame('error.stream', $response->getContent());
    }

    public function testCreateVariantTemplate(): void
    {
        $loader = $this->mockContaoFilesystemLoader();
        $loader
            ->expects($this->once())
            ->method('warmUp')
            ->with(true)
        ;

        $storage = $this->mockUserTemplatesStorage(['prefix/foo.html.twig']);
        $storage
            ->expects($this->once())
            ->method('write')
            ->with('prefix/foo/my_variant.html.twig', 'new template content')
        ;

        $twig = $this->mockTwigEnvironment();
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/template_studio/operation/create_variant_result.stream.html.twig',
                ['identifier' => 'prefix/foo/my_variant'],
            )
            ->willReturn('create_variant_result.stream')
        ;

        $operation = $this->getCreateVariantOperation(
            $loader,
            $storage,
            $twig,
            $this->mockTemplateSkeletonFactory('@Contao/prefix/foo.html.twig'),
        );

        $response = $operation->execute(
            new Request(request: ['identifier_fragment' => 'my_variant']),
            $this->getOperationContext('prefix/foo'),
        );

        $this->assertSame('create_variant_result.stream', $response->getContent());
    }

    private function getCreateVariantOperation(ContaoFilesystemLoader|null $loader = null, VirtualFilesystemInterface|null $storage = null, Environment|null $twig = null, TemplateSkeletonFactory|null $skeletonFactory = null): AbstractCreateVariantOperation
    {
        $operation = new class() extends AbstractCreateVariantOperation {
            protected function getPrefix(): string
            {
                return 'prefix';
            }
        };

        $operation->setContainer($this->getContainer($loader, $storage, $twig, $skeletonFactory));
        $operation->setName('create_prefix_variant');

        return $operation;
    }
}
