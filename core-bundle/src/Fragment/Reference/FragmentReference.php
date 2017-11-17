<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Fragment\Reference;

use Contao\CoreBundle\ContaoCoreBundle;
use Symfony\Component\HttpKernel\Controller\ControllerReference;

class FragmentReference extends ControllerReference
{
    /**
     * {@inheritdoc}
     */
    public function __construct($fragment, array $attributes = [], array $query = [])
    {
        parent::__construct($fragment, $attributes, $query);

        if (!isset($this->attributes['scope'])) {
            $this->attributes['scope'] = ContaoCoreBundle::SCOPE_FRONTEND;
        }
    }

    /**
     * Sets the front end scope.
     */
    public function setFrontendScope(): void
    {
        $this->attributes['scope'] = ContaoCoreBundle::SCOPE_FRONTEND;
    }

    /**
     * Checks if the fragment is in front end scope.
     *
     * @return bool
     */
    public function isFrontendScope(): bool
    {
        return ContaoCoreBundle::SCOPE_FRONTEND === $this->attributes['scope'];
    }

    /**
     * Sets the back end scope.
     */
    public function setBackendScope(): void
    {
        $this->attributes['scope'] = ContaoCoreBundle::SCOPE_BACKEND;
    }

    /**
     * Checks if the fragment is in back end scope.
     *
     * @return bool
     */
    public function isBackendScope(): bool
    {
        return ContaoCoreBundle::SCOPE_BACKEND === $this->attributes['scope'];
    }
}
