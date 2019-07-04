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

    public function setTokenStorage(TokenStorageInterface $tokenStorage): void
    {
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
     */
    protected function getUser(): BackendUser
    {
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
     * Provides a shortcut to get the "insertTag" extra value for child classes.
     */
    protected function getInsertTag(PickerConfig $config): string
    {
        if ($insertTag = $config->getExtraForProvider('insertTag', $this->getName())) {
            return (string) $insertTag;
        }

        return $this->getFallbackInsertTag();
    }

    /**
     * Provides a shortcut get the "insertTag" extra value for child classes and
     * split them at the placeholder (%s).
     */
    protected function getInsertTagChunks(PickerConfig $config): array
    {
        return explode('%s', $this->getInsertTag($config), 2);
    }

    /**
     * Defines the fallback insert tag to work with if no specifc configuration
     * was provided.
     */
    protected function getFallbackInsertTag(): string
    {
        throw new \RuntimeException('If you deal with insert tags in your picker provider you have to specify a
        fallback insert tag in case no specific insert tag was passed on via configuration. You must override
        the AbstractPickerProvider::getFallbackInsertTag() method.');
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
