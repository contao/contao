<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use KnpU\OAuth2ClientBundle\DependencyInjection\KnpUOAuth2ClientExtension;

#[AsCallback('tl_oauth_client', 'fields.type.options')]
class OAuthClientTypeOptionsListener
{
    public function __construct(private readonly array $enabledProviders)
    {
    }

    public function __invoke(): array
    {
        $options = [];
        $extension = new KnpUOAuth2ClientExtension();

        foreach (KnpUOAuth2ClientExtension::getAllSupportedTypes() as $type) {
            if ('generic' === $type || !\in_array($type, $this->enabledProviders, true)) {
                continue;
            }

            $config = $extension->getConfigurator($type);
            $options[$type] = $config->getProviderDisplayName();
        }

        return $options;
    }
}
