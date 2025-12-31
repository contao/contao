<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Form\Type;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * @template T
 *
 * @extends AbstractType<T>
 */
abstract class AbstractContaoFormType extends AbstractType implements ServiceSubscriberInterface
{
    protected ContainerInterface $container;

    #[Required]
    public function setContainer(ContainerInterface $container): ContainerInterface|null
    {
        $previous = $this->container ?? null;
        $this->container = $container;

        return $previous;
    }

    public static function getSubscribedServices(): array
    {
        $services['contao.csrf.token_manager'] = ContaoCsrfTokenManager::class;
        $services['parameter_bag'] = ParameterBagInterface::class;

        return $services;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_field_name' => 'REQUEST_TOKEN',
            'csrf_token_manager' => $this->container->get('contao.csrf.token_manager'),
            'csrf_token_id' => $this->container->get('parameter_bag')->get('contao.csrf_token_name'),
            'translation_domain' => 'contao_default',
        ]);
    }
}
