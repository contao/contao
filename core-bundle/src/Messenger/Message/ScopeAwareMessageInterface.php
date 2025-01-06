<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\Message;

interface ScopeAwareMessageInterface
{
    final public const SCOPE_WEB = 'web';

    final public const SCOPE_CLI = 'cli';

    public function getScope(): string;

    public function setScope(string $scope): self;
}
