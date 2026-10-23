<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\ApiPlatform\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Reference;
use ApiPlatform\OpenApi\Model\Schema;
use ApiPlatform\OpenApi\OpenApi;
use Contao\ApiBundle\ApiPlatform\OpenApi\ApiVersionOpenApiFactory;
use Contao\ApiBundle\Version\ApiVersion;
use Contao\ApiBundle\Version\ApiVersionProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class ApiVersionOpenApiFactoryTest extends TestCase
{
    public function testGeneratesDifferentSchemasForSuccessiveRequestVersions(): void
    {
        $decorated = new class implements OpenApiFactoryInterface {
            public function __invoke(array $context = []): OpenApi
            {
                $version = $context[ApiVersion::CONTEXT_KEY];
                $property = $version->isBefore('2024-03-01') ? 'name' : 'displayName';

                $schema = new Schema();
                $schema['type'] = 'object';
                $schema['properties'] = [$property => ['type' => 'string']];

                return new OpenApi(new Info('API', '1.0'), [], new Paths(), new Components(schemas: new \ArrayObject([
                    'Example' => $schema,
                ])));
            }
        };
        $requests = new RequestStack();
        $factory = new ApiVersionOpenApiFactory($decorated, new ApiVersionProvider($requests));

        foreach (['2024-02-29' => 'name', '2024-03-01' => 'displayName', '2024-02-28' => 'name'] as $date => $property) {
            $request = new Request();
            $request->attributes->set(ApiVersion::CONTEXT_KEY, new ApiVersion($date));
            $requests->push($request);

            $spec = $factory(['request' => $request]);

            $this->assertSame([$property => ['type' => 'string']], $spec->getComponents()->getSchemas()['Example']['properties']);
            $this->assertSame($date, $spec->getInfo()->getVersion());
            $this->assertArrayNotHasKey('x-contao-api-version', $spec->getExtensionProperties());
            $this->assertSame($date, $spec->getComponents()->getParameters()['ContaoApiVersion']->getExample());

            $requests->pop();
        }
    }

    public function testDocumentsOneSharedHeaderForAllPathsWithoutMutatingTheOriginal(): void
    {
        $existing = new Parameter('id', 'path', required: true);

        $paths = new Paths();
        $paths->addPath('/dc/example/{id}', new PathItem(get: new Operation(), patch: new Operation(), parameters: [$existing]));
        $paths->addPath('/custom', new PathItem(post: new Operation()));

        $components = new Components(parameters: new \ArrayObject(['Existing' => $existing]));
        $original = new OpenApi(new Info('API', '1.0'), [], $paths, $components);
        $version = ApiVersion::today();

        $decorated = $this->createMock(OpenApiFactoryInterface::class);
        $decorated
            ->expects($this->once())
            ->method('__invoke')
            ->with(['example' => true, ApiVersion::CONTEXT_KEY => $version])
            ->willReturn($original)
        ;

        $result = new ApiVersionOpenApiFactory($decorated, new ApiVersionProvider(new RequestStack()))(['example' => true, ApiVersion::CONTEXT_KEY => $version]);
        $this->assertSame((string) $version, $result->getInfo()->getVersion());
        $this->assertSame('1.0', $original->getInfo()->getVersion());

        $header = $result->getComponents()->getParameters()['ContaoApiVersion'];
        $this->assertSame(ApiVersion::HEADER, $header->getName());
        $this->assertSame('header', $header->getIn());

        $today = new \DateTimeImmutable('today', new \DateTimeZone('UTC'))->format('Y-m-d');
        $this->assertSame($today, $header->getExample());
        $this->assertStringContainsString("today's date", $header->getDescription());
        $this->assertStringContainsString(ApiVersion::HEADER.': '.$today, $header->getDescription());
        $this->assertSame(['type' => 'string', 'format' => 'date'], $header->getSchema());
        $this->assertSame($existing, $result->getComponents()->getParameters()['Existing']);
        $this->assertSame([$existing], $original->getPaths()->getPath('/dc/example/{id}')->getParameters());
        $this->assertCount(1, $original->getComponents()->getParameters());

        foreach ($result->getPaths()->getPaths() as $path) {
            $parameters = $path->getParameters();
            $reference = $parameters[array_key_last($parameters)];
            $this->assertInstanceOf(Reference::class, $reference);
            $this->assertSame('#/components/parameters/ContaoApiVersion', $reference->getRef());
        }

        $again = $this->createStub(OpenApiFactoryInterface::class);
        $again
            ->method('__invoke')
            ->willReturn($result)
        ;

        $this->assertCount(2, new ApiVersionOpenApiFactory($again, new ApiVersionProvider(new RequestStack()))()->getPaths()->getPath('/dc/example/{id}')->getParameters());
    }
}
