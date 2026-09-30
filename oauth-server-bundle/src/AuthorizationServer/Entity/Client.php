<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\OAuthServerBundle\AuthorizationServer\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

/**
 * @internal
 */
final class Client implements ClientEntityInterface
{
    use ClientTrait;
    use EntityTrait;

    public function __construct(string $identifier, string $name, array $redirectUris)
    {
        $this->setIdentifier($identifier);
        $this->name = $name;
        $this->redirectUri = $redirectUris;
        $this->isConfidential = false;
    }
}
