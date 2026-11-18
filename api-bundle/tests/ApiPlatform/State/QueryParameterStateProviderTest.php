<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\ApiPlatform\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\State\Exception\ParameterNotSupportedException;
use ApiPlatform\State\Pagination\PaginationOptions;
use ApiPlatform\State\ProviderInterface;
use Contao\ApiBundle\ApiPlatform\State\QueryParameterStateProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class QueryParameterStateProviderTest extends TestCase
{
    public function testReturnsTheResultOfTheDecoratedProvider(): void
    {
        $result = new \stdClass();

        $decorated = $this->createMock(ProviderInterface::class);
        $decorated
            ->expects($this->once())
            ->method('provide')
            ->with($this->isInstanceOf(Get::class), ['id' => 1], ['foo' => 'bar'])
            ->willReturn($result)
        ;

        $provider = new QueryParameterStateProvider($decorated, new PaginationOptions());

        $this->assertSame($result, $provider->provide(new Get(), ['id' => 1], ['foo' => 'bar']));
    }

    #[DataProvider('provideOperations')]
    public function testNamesTheUnknownAndTheSupportedQueryParameters(Operation $operation, string $message): void
    {
        $decorated = $this->createStub(ProviderInterface::class);
        $decorated
            ->method('provide')
            ->willThrowException(new ParameterNotSupportedException('pid'))
        ;

        $provider = new QueryParameterStateProvider($decorated, new PaginationOptions(clientItemsPerPage: true));

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage($message);

        $provider->provide($operation);
    }

    public static function provideOperations(): iterable
    {
        yield 'collection' => [
            new GetCollection(parameters: ['sort' => new QueryParameter(key: 'sort'), 'version' => new HeaderParameter(key: 'Contao-Api-Version')]),
            'The query parameter "pid" is not supported. Supported query parameters: sort, page, itemsPerPage.',
        ];

        yield 'collection without client page size' => [
            new GetCollection(paginationClientItemsPerPage: false),
            'The query parameter "pid" is not supported. Supported query parameters: page.',
        ];

        yield 'record' => [
            new Get(),
            'The query parameter "pid" is not supported. This operation does not accept query parameters.',
        ];
    }
}
