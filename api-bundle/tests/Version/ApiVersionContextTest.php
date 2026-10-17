<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\Version;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\SerializerContextBuilderInterface;
use Contao\ApiBundle\ApiPlatform\Serializer\ApiVersionContextBuilder;
use Contao\ApiBundle\ApiPlatform\State\ApiVersionStateProcessor;
use Contao\ApiBundle\ApiPlatform\State\ApiVersionStateProvider;
use Contao\ApiBundle\Version\ApiVersion;
use Contao\ApiBundle\Version\ApiVersionProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class ApiVersionContextTest extends TestCase
{
    public function testProvidersReceiveVersionAndKeepExistingContext(): void
    {
        $request = new Request();
        $version = new ApiVersion('2024-02-29');
        $request->attributes->set(ApiVersion::CONTEXT_KEY, $version);
        $operation = new Get();
        $data = new \stdClass();
        $decorated = $this->createMock(ProviderInterface::class);
        $decorated
            ->expects($this->once())
            ->method('provide')
            ->with($operation, ['id' => 42], ['request' => $request, 'filters' => ['foo' => 'bar'], ApiVersion::CONTEXT_KEY => $version])
            ->willReturn($data)
        ;

        $provider = new ApiVersionStateProvider($decorated, new ApiVersionProvider(new RequestStack()));

        $this->assertSame($data, $provider->provide($operation, ['id' => 42], ['request' => $request, 'filters' => ['foo' => 'bar']]));
    }

    public function testProcessorsReceiveTheCurrentRequestVersion(): void
    {
        $request = new Request();
        $version = new ApiVersion('2024-02-29');
        $request->attributes->set(ApiVersion::CONTEXT_KEY, $version);
        $requests = new RequestStack();
        $requests->push($request);

        $operation = new Post();
        $data = new \stdClass();
        $decorated = $this->createMock(ProcessorInterface::class);
        $decorated
            ->expects($this->once())
            ->method('process')
            ->with($data, $operation, [], ['previous_data' => null, ApiVersion::CONTEXT_KEY => $version])
            ->willReturn($data)
        ;

        $processor = new ApiVersionStateProcessor($decorated, new ApiVersionProvider($requests));

        $this->assertSame($data, $processor->process($data, $operation, context: ['previous_data' => null]));
    }

    public function testBothSerializerContextsReceiveTheSameVersion(): void
    {
        $request = new Request();
        $version = new ApiVersion('2024-02-29');
        $request->attributes->set(ApiVersion::CONTEXT_KEY, $version);
        $decorated = $this->createMock(SerializerContextBuilderInterface::class);
        $decorated
            ->expects($this->exactly(2))
            ->method('createFromRequest')
            ->with($request, $this->anything(), ['resource_class' => \stdClass::class])
            ->willReturn(['groups' => ['example']])
        ;
        $builder = new ApiVersionContextBuilder($decorated, new ApiVersionProvider(new RequestStack()));

        foreach ([true, false] as $normalization) {
            $this->assertSame(['groups' => ['example'], ApiVersion::CONTEXT_KEY => $version], $builder->createFromRequest($request, $normalization, ['resource_class' => \stdClass::class]));
        }
    }

    public function testAccessorTracksSubrequestsAndFallsBackToTodayOutsideHttp(): void
    {
        $requests = new RequestStack();
        $provider = new ApiVersionProvider($requests);
        $this->assertSame(new \DateTimeImmutable('today', new \DateTimeZone('UTC'))->format('Y-m-d'), (string) $provider->getVersion());

        $parent = new Request();
        $parentVersion = new ApiVersion('2024-02-28');
        $parent->attributes->set(ApiVersion::CONTEXT_KEY, $parentVersion);
        $requests->push($parent);
        $child = new Request();
        $childVersion = new ApiVersion('2024-02-29');
        $child->attributes->set(ApiVersion::CONTEXT_KEY, $childVersion);
        $requests->push($child);

        $this->assertSame($childVersion, $provider->getVersion());
        $requests->pop();
        $this->assertSame($parentVersion, $provider->getVersion());
    }
}
