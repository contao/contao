<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fragment\Reference;

use Contao\CoreBundle\ContaoCoreBundle;
use Symfony\Component\HttpKernel\Controller\ControllerReference;

class FragmentReference extends ControllerReference
{
    public function __construct(string $fragment, array $attributes = [], array $query = [])
    {
        parent::__construct($fragment, $attributes, $query);

        if (!isset($this->attributes['_scope'])) {
            $this->attributes['_scope'] = ContaoCoreBundle::SCOPE_FRONTEND;
        }
    }

    public function setFrontendScope(): void
    {
        $this->attributes['_scope'] = ContaoCoreBundle::SCOPE_FRONTEND;
    }

    public function isFrontendScope(): bool
    {
        return ContaoCoreBundle::SCOPE_FRONTEND === $this->attributes['_scope'];
    }

    public function setBackendScope(): void
    {
        $this->attributes['_scope'] = ContaoCoreBundle::SCOPE_BACKEND;
    }

    public function isBackendScope(): bool
    {
        return ContaoCoreBundle::SCOPE_BACKEND === $this->attributes['_scope'];
    }
}
