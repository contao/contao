<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle;

use Doctrine\DBAL\Connection;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use KnpU\OAuth2ClientBundle\Client\Provider\AmazonClient;
use KnpU\OAuth2ClientBundle\Client\Provider\FacebookClient;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use KnpU\OAuth2ClientBundle\Client\Provider\SlackClient;
use KnpU\OAuth2ClientBundle\DependencyInjection\KnpUOAuth2ClientExtension;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Generates an OAuth2Client.
 */
class OAuthClientGenerator
{
    public function __construct(private readonly Connection $db, private readonly RequestStack $requestStack, private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function getClientById(int $clientId)
    {
        $clientConfig = $this->db->fetchAssociative('SELECT * FROM tl_oauth_client WHERE id = ?', [$clientId]);

        if (false === $clientConfig) {
            throw new \InvalidArgumentException('Invalid client ID.');
        }

        return $this->getClient($clientConfig);
    }

    public function getClient(array $clientConfig): OAuth2Client
    {
        $type = $clientConfig['type'];
        $extension = new KnpUOAuth2ClientExtension();
        $configurator = $extension->getConfigurator($type);
        $providerClass = $configurator->getProviderClass($clientConfig);

        if (!class_exists($providerClass)) {
            throw new \LogicException(sprintf('Run `composer require %s` in order to use the "%s" OAuth provider.', $configurator->getPackagistName(), $type));
        }

        $providerOptions = $configurator->getProviderOptions($clientConfig);
        $providerOptions['redirectUri'] = $this->urlGenerator->generate('contao_oauth_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $provider = new $providerClass($providerOptions);
        $clientClass = $configurator->getClientClass($clientConfig);

        return new $clientClass($provider, $this->requestStack);
    }

    public function getDefaultScopes(OAuth2Client $oauthClient): array
    {
        $scopes = [];

        if ($oauthClient instanceof AmazonClient) {
            $scopes = ['identity.basic', 'identity.email'];
        } elseif ($oauthClient instanceof FacebookClient) {
            $scopes = ['email'];
        } elseif ($oauthClient instanceof GoogleClient) {
            $scopes = ['userinfo.email', 'userinfo.profile'];
        } elseif ($oauthClient instanceof SlackClient) {
            $scopes = ['profile'];
        }

        return $scopes;
    }
}
