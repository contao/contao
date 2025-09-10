<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Form\Type;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Form\Type\ChangePasswordType;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangePasswordTypeTest extends TestCase
{
    public function testConfiguresOptions(): void
    {
        $formType = new ChangePasswordType();

        $container = new ContainerBuilder();
        $container->set('contao.csrf.token_manager', $this->createMock(ContaoCsrfTokenManager::class));
        $container->set('parameter_bag', $this->createMock(ParameterBagInterface::class));

        $optionsResolver = $this->createMock(OptionsResolver::class);
        $optionsResolver
            ->expects($this->once())
            ->method('setDefaults')
        ;

        $formType->setContainer($container);
        $formType->configureOptions($optionsResolver);

        $this->assertSame(
            [
                'contao.csrf.token_manager' => '?'.ContaoCsrfTokenManager::class,
                'parameter_bag' => '?'.ParameterBagInterface::class,
            ],
            $formType::getSubscribedServices(),
        );
    }

    public function testBuildForm(): void
    {
        $formType = new ChangePasswordType();

        $builder = $this->createMock(FormBuilder::class);
        $builder
            ->expects($this->exactly(3))
            ->method('add')
            ->willReturn($builder)
        ;

        $formType->buildForm($builder, []);
    }
}
