<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig;

use Contao\Model;

/**
 * Simple proxy class to provide access to model data in Twig templates. We also
 * implement JsonSerializable for the ContentElementTestCase.
 */
class ModelProxy implements \JsonSerializable
{
    public function __construct(private readonly Model $model)
    {
    }

    public function __get(string $key): mixed
    {
        return $this->model->__get($key);
    }

    public function __isset(string $key)
    {
        return null !== $this->model->__get($key);
    }

    public function jsonSerialize(): mixed
    {
        return $this->model->row();
    }
}
