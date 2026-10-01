<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\ApiPlatform\Metadata;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\ApiPlatform\Metadata\UserTemplateResourceMetadataCollectionFactory;
use Contao\ApiBundle\Dto\UserTemplate;
use Contao\CoreBundle\Twig\Studio\Operation\AbstractOperation;
use Contao\CoreBundle\Twig\Studio\Operation\DeleteOperation;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class UserTemplateResourceMetadataCollectionFactoryTest extends TestCase
{
    public function testDelegatesOtherResources(): void
    {
        $collection = new ResourceMetadataCollection('App\\Resource');

        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated
            ->expects($this->once())
            ->method('create')
            ->with('App\Resource')
            ->willReturn($collection)
        ;

        $this->assertSame($collection, new UserTemplateResourceMetadataCollectionFactory($decorated, [])->create('App\\Resource'));
    }

    public function testDelegatesTheUserTemplateResourceIfTheTemplateStudioIsDisabled(): void
    {
        $collection = new ResourceMetadataCollection(UserTemplate::class);

        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated
            ->expects($this->once())
            ->method('create')
            ->with(UserTemplate::class)
            ->willReturn($collection)
        ;

        $factory = new UserTemplateResourceMetadataCollectionFactory($decorated, [], false);

        $this->assertSame($collection, $factory->create(UserTemplate::class));
    }

    public function testExposesRegisteredOperationsOnceAndDelegatesDispatch(): void
    {
        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated
            ->expects($this->never())
            ->method('create')
        ;

        $save = $this->createStub(AbstractOperation::class);
        $save
            ->method('getName')
            ->willReturn('save')
        ;

        $custom = $this->createStub(AbstractOperation::class);
        $custom
            ->method('getName')
            ->willReturn('custom')
        ;

        $create = $this->createStub(AbstractOperation::class);
        $create
            ->method('getName')
            ->willReturn('create')
        ;

        $delete = new DeleteOperation();
        $delete->setName('delete');

        $resources = new UserTemplateResourceMetadataCollectionFactory($decorated, [$save, $save, $create, $delete, $custom])->create(UserTemplate::class);
        $operations = iterator_to_array($resources[0]->getOperations());

        $this->assertCount(7, $operations);
        $this->assertSame('/user_template_themes', $operations['contao_api_user_template_theme_discover']->getUriTemplate());
        $this->assertInstanceOf(Get::class, $operations['contao_api_user_template_read']);
        $this->assertSame('/user_templates', $operations['contao_api_user_template_discover']->getUriTemplate());
        $this->assertSame('/user_templates/{name}', $operations['contao_api_user_template_read']->getUriTemplate());
        $this->assertSame('contao_api.api_platform.user_template_state_provider', $operations['contao_api_user_template_read']->getProvider());
        $this->assertNotEmpty($operations['contao_api_user_template_discover']->getDescription());
        $this->assertNotEmpty($operations['contao_api_user_template_read']->getDescription());

        foreach (['create', 'custom'] as $name) {
            $operation = $operations['contao_api_user_template_operation_'.$name];
            $this->assertInstanceOf(Post::class, $operation);
            $this->assertSame('/user_template_operations/'.$name, $operation->getUriTemplate());
            $this->assertSame("is_granted('ROLE_ADMIN')", $operation->getSecurity());
            $this->assertSame('contao_api.api_platform.user_template_state_processor', $operation->getProcessor());
            $this->assertSame($name, $operation->getExtraProperties()['template_studio_operation']);
            $this->assertFalse($operation->canRead());
            $this->assertNotEmpty($operation->getDescription());
        }

        $this->assertInstanceOf(Patch::class, $operations['contao_api_user_template_operation_save']);
        $this->assertSame('/user_templates/{name}', $operations['contao_api_user_template_operation_save']->getUriTemplate());

        $this->assertInstanceOf(Delete::class, $operations['contao_api_user_template_operation_delete']);
        $this->assertSame('/user_templates/{name}', $operations['contao_api_user_template_operation_delete']->getUriTemplate());
        $this->assertFalse($operations['contao_api_user_template_operation_delete']->getInput());
        $this->assertStringContainsString('Delete the user template', $operations['contao_api_user_template_operation_delete']->getDescription());
        $this->assertStringContainsString('deletion is immediate', $operations['contao_api_user_template_operation_delete']->getDescription());

        foreach ($operations as $operation) {
            $this->assertSame('UserTemplate', $operation->getShortName());

            $parameters = iterator_to_array($operation->getParameters());

            $this->assertArrayHasKey('theme', $parameters);
            $this->assertSame('theme', $parameters['theme']->getKey());
        }

        $this->assertArrayHasKey('query', iterator_to_array($operations['contao_api_user_template_discover']->getParameters()));
    }

    public function testMatchesEncodedIdentifierSlashesBeforeTheOperation(): void
    {
        $save = $this->createStub(AbstractOperation::class);
        $save
            ->method('getName')
            ->willReturn('save')
        ;

        $factory = new UserTemplateResourceMetadataCollectionFactory($this->createStub(ResourceMetadataCollectionFactoryInterface::class), [$save]);
        $operation = iterator_to_array($factory->create(UserTemplate::class)[0]->getOperations())['contao_api_user_template_operation_save'];

        $routes = new RouteCollection();
        $routes->add($operation->getName(), new Route(
            '/contao/_api'.$operation->getUriTemplate(),
            requirements: $operation->getRequirements(),
            methods: ['PATCH'],
        ));

        $matcher = new UrlMatcher($routes, new RequestContext(method: 'PATCH'));
        $parameters = $matcher->match('/contao/_api/user_templates/content_element%2Ftext%2Fsave');

        $this->assertSame('content_element/text/save', $parameters['name']);
        $this->assertSame('contao_api_user_template_operation_save', $parameters['_route']);
    }

    public function testMatchesOperationRoutes(): void
    {
        $create = $this->createStub(AbstractOperation::class);
        $create
            ->method('getName')
            ->willReturn('create')
        ;

        $custom = $this->createStub(AbstractOperation::class);
        $custom
            ->method('getName')
            ->willReturn('custom')
        ;

        $factory = new UserTemplateResourceMetadataCollectionFactory($this->createStub(ResourceMetadataCollectionFactoryInterface::class), [$create, $custom]);
        $routes = new RouteCollection();

        foreach ($factory->create(UserTemplate::class)[0]->getOperations() as $operation) {
            if (!$operation instanceof Post) {
                continue;
            }

            $routes->add($operation->getName(), new Route(
                '/contao/_api'.$operation->getUriTemplate(),
                requirements: $operation->getRequirements() ?? [],
                methods: ['POST'],
            ));
        }

        $parameters = new UrlMatcher($routes, new RequestContext(method: 'POST'))->match('/contao/_api/user_template_operations/custom');

        $this->assertSame('contao_api_user_template_operation_custom', $parameters['_route']);
    }
}
