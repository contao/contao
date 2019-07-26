<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Picker;

use Contao\BackendUser;
use Knp\Menu\FactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

abstract class AbstractPickerProvider implements PickerProviderInterface
{
    /**
     * @var FactoryInterface
     */
    private $menuFactory;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TranslatorInterface|null
     */
    private $translator;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(FactoryInterface $menuFactory, RouterInterface $router, TranslatorInterface $translator = null)
    {
        $this->menuFactory = $menuFactory;
        $this->router = $router;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl(PickerConfig $config)
    {
        return $this->generateUrl($config, false);
    }

    /**
     * {@inheritdoc}
     */
    public function createMenuItem(PickerConfig $config)
    {
        $name = $this->getName();

        if (null === $this->translator) {
            @trigger_error('Using a picker provider without injecting the translator service has been deprecated and will no longer work in Contao 5.0.', E_USER_DEPRECATED);
            $label = $GLOBALS['TL_LANG']['MSC'][$name];
        } else {
            $label = $this->translator->trans('MSC.'.$name, [], 'contao_default');
        }

        return $this->menuFactory->createItem(
            $name,
            [
                'label' => $label ?: $name,
                'linkAttributes' => ['class' => $name],
                'current' => $this->isCurrent($config),
                'uri' => $this->generateUrl($config, true),
            ]
        );
    }

    /**
     * @deprecated Deprecated since Contao 4.8, to be removed in Contao 5.0;
     *             use Symfony security instead
     */
    public function setTokenStorage(TokenStorageInterface $tokenStorage): void
    {
        @trigger_error('Using AbstractPickerProvider::setTokenStorage() has been deprecated and will no longer work in Contao 5.0. Use Symfony security instead.', E_USER_DEPRECATED);

        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function isCurrent(PickerConfig $config)
    {
        return $config->getCurrent() === $this->getName();
    }

    /**
     * Returns the back end user object.
     *
     * @throws \RuntimeException
     *
     * @deprecated Deprecated since Contao 4.8, to be removed in Contao 5.0;
     *             use Symfony security instead
     */
    protected function getUser(): BackendUser
    {
        @trigger_error('Using AbstractPickerProvider::getUser() has been deprecated and will no longer work in Contao 5.0. Use Symfony security instead.', E_USER_DEPRECATED);

        if (null === $this->tokenStorage) {
            throw new \RuntimeException('No token storage provided');
        }

        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new \RuntimeException('No token provided');
        }

        $user = $token->getUser();

        if (!$user instanceof BackendUser) {
            throw new \RuntimeException('The token does not contain a back end user object');
        }

        return $user;
    }

    /**
     * Returns the routing parameters for the back end picker.
     *
     * @return array<string,string|int>
     */
    abstract protected function getRouteParameters(PickerConfig $config = null);

    /**
     * Generates the URL for the picker.
     */
    private function generateUrl(PickerConfig $config, bool $ignoreValue): ?string
    {
        $params = array_merge(
            $this->getRouteParameters($ignoreValue ? null : $config),
            [
                'popup' => '1',
                'picker' => $config->cloneForCurrent($this->getName())->urlEncode(),
            ]
        );

        return $this->router->generate('contao_backend', $params);
    }
}
