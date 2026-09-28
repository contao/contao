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

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\ApiPlatform\Metadata\UserTemplateResourceMetadataCollectionFactory;
use Contao\ApiBundle\ApiPlatform\State\UserTemplateStateProcessor;
use Contao\ApiBundle\ApiPlatform\State\UserTemplateStateProvider;
use Contao\ApiBundle\Resource\UserTemplate;
use Contao\CoreBundle\Twig\Studio\Operation\AbstractOperation;
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
            ->with('App\\Resource')
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
        $resources = new UserTemplateResourceMetadataCollectionFactory($decorated, [$save, $save, $custom])->create(UserTemplate::class);
        $operations = iterator_to_array($resources[0]->getOperations());

        $this->assertCount(4, $operations);
        $this->assertInstanceOf(Get::class, $operations['contao_api_user_template_read']);
        $this->assertSame('/user_template', $operations['contao_api_user_template_discover']->getUriTemplate());
        $this->assertSame('/user_template/{identifier}', $operations['contao_api_user_template_read']->getUriTemplate());
        $this->assertSame(UserTemplateStateProvider::class, $operations['contao_api_user_template_read']->getProvider());
        $this->assertNotEmpty($operations['contao_api_user_template_discover']->getDescription());
        $this->assertNotEmpty($operations['contao_api_user_template_read']->getDescription());

        foreach (['save', 'custom'] as $name) {
            $operation = $operations['contao_api_user_template_operation_'.$name];
            $this->assertInstanceOf(Post::class, $operation);
            $this->assertSame('/user_template/{identifier}/'.$name, $operation->getUriTemplate());
            $this->assertSame("is_granted('ROLE_ADMIN')", $operation->getSecurity());
            $this->assertSame(UserTemplateStateProcessor::class, $operation->getProcessor());
            $this->assertSame($name, $operation->getExtraProperties()['template_studio_operation']);
            $this->assertFalse($operation->canRead());
            $this->assertNotEmpty($operation->getDescription());
        }
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
            methods: ['POST'],
        ));
        $matcher = new UrlMatcher($routes, new RequestContext(method: 'POST'));
        $parameters = $matcher->match('/contao/_api/user_template/content_element%2Ftext%2Fsave/save');

        $this->assertSame('content_element/text/save', $parameters['identifier']);
        $this->assertSame('contao_api_user_template_operation_save', $parameters['_route']);
    }
}
