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

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Serializer\SerializerContextBuilder;
use ApiPlatform\State\CallableProcessor;
use ApiPlatform\State\CallableProvider;
use ApiPlatform\State\Processor\RespondProcessor;
use ApiPlatform\State\Processor\SerializeProcessor;
use ApiPlatform\State\Processor\WriteProcessor;
use ApiPlatform\State\Provider\DeserializeProvider;
use ApiPlatform\State\Provider\ReadProvider;
use ApiPlatform\Symfony\Controller\MainController;
use Contao\ApiBundle\ApiPlatform\Serializer\ApiVersionContextBuilder;
use Contao\ApiBundle\ApiPlatform\State\ApiVersionStateProcessor;
use Contao\ApiBundle\ApiPlatform\State\ApiVersionStateProvider;
use Contao\ApiBundle\EventListener\ApiVersionListener;
use Contao\ApiBundle\Version\ApiVersion;
use Contao\ApiBundle\Version\ApiVersionProvider;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Serializer\SerializerInterface;

final class ApiVersionHttpTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([Request::class]);

        parent::tearDown();
    }

    public function testVersionReachesTheReadWriteAndSerializationPipelines(): void
    {
        $requests = new RequestStack();
        $versions = new ApiVersionProvider($requests);
        $contexts = [];

        $operation = new Post(
            class: \stdClass::class,
            read: true,
            deserialize: true,
            write: true,
            serialize: true,
            uriVariables: [],
            provider: static function (Operation $operation, array $uriVariables, array $context) use (&$contexts): object {
                $contexts[] = $context[ApiVersion::CONTEXT_KEY];

                return new \stdClass();
            },
            processor: static function (mixed $data, Operation $operation, array $uriVariables, array $context) use (&$contexts): object {
                $contexts[] = $context[ApiVersion::CONTEXT_KEY];

                return $data;
            },
        );

        $contextBuilder = new ApiVersionContextBuilder(new SerializerContextBuilder(), $versions);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer
            ->expects($this->once())
            ->method('deserialize')
            ->willReturnCallback(
                static function (string $data, string $type, string $format, array $context) use (&$contexts): object {
                    $contexts[] = $context[ApiVersion::CONTEXT_KEY];

                    return (object) json_decode($data, true, flags: JSON_THROW_ON_ERROR);
                },
            )
        ;
        $serializer
            ->expects($this->once())
            ->method('serialize')
            ->willReturnCallback(
                static function (object $data, string $format, array $context) use (&$contexts): string {
                    $contexts[] = $context[ApiVersion::CONTEXT_KEY];

                    return json_encode($data, JSON_THROW_ON_ERROR);
                },
            )
        ;

        $provider = new DeserializeProvider(new ReadProvider(new ApiVersionStateProvider(new CallableProvider(), $versions), $contextBuilder), $serializer, $contextBuilder);
        $processor = new WriteProcessor(new SerializeProcessor(new RespondProcessor(), $serializer, $contextBuilder), new ApiVersionStateProcessor(new CallableProcessor(), $versions));
        $controller = new MainController($this->createStub(ResourceMetadataCollectionFactoryInterface::class), $provider, $processor);
        $request = Request::create('/api/example', 'POST', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_CONTAO_API_VERSION' => '2024-02-29'], content: '{"title":"example"}');

        $kernel = $this->createKernel($controller, $operation, $requests);
        $response = $kernel->handle($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('{"title":"example"}', $response->getContent());
        $this->assertSame('2024-02-29', $response->headers->get(ApiVersion::HEADER));
        $this->assertContains(ApiVersion::HEADER, $response->getVary());
        $this->assertCount(4, $contexts);

        foreach ($contexts as $version) {
            $this->assertSame($request->attributes->get(ApiVersion::CONTEXT_KEY), $version);
        }
    }

    public function testInvalidHeadersPreventControllerExecutionAndProduceHttp400(): void
    {
        $controller = static function (): never {
            throw new \LogicException('The controller must not run.');
        };

        $request = Request::create('/api/example', 'POST', server: ['HTTP_CONTAO_API_VERSION' => 'invalid']);

        $kernel = $this->createKernel($controller, new Post(), new RequestStack());
        $response = $kernel->handle($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertContains(ApiVersion::HEADER, $response->getVary());
    }

    private function createKernel(callable $controller, Operation $operation, RequestStack $requests): HttpKernel
    {
        $routes = new RouteCollection();
        $routes->add('example', new Route('/api/example', [
            '_controller' => $controller,
            '_api_operation' => $operation,
            '_api_resource_class' => \stdClass::class,
            '_format' => 'json',
            'input_format' => 'json',
        ]));

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new RouterListener(new UrlMatcher($routes, new RequestContext()), $requests));

        $listener = new ApiVersionListener($requests);

        $dispatcher->addListener(KernelEvents::REQUEST, $listener->onKernelRequest(...), 28);
        $dispatcher->addListener(KernelEvents::RESPONSE, $listener->onKernelResponse(...), -128);

        $dispatcher->addListener(
            KernelEvents::EXCEPTION,
            static function (ExceptionEvent $event): void {
                if ($event->getThrowable() instanceof BadRequestHttpException) {
                    $event->setResponse(new JsonResponse(['error' => $event->getThrowable()->getMessage()], 400));
                }
            },
        );

        return new HttpKernel($dispatcher, new ControllerResolver(), $requests, new ArgumentResolver());
    }
}
