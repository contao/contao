<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\CoreBundle\Autoload;

/**
 * Provides methods to access the configuration
 *
 * @author Leo Feyer <https://contao.org>
 */
class Config implements ConfigInterface
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $replace = [];

    /**
     * @var array
     */
    protected $environments = ['all'];

    /**
     * @var array
     */
    protected $loadAfter = [];

    /**
     * {@inheritdoc}
     */
    public static function create()
    {
        return new static();
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReplace()
    {
        return $this->replace;
    }

    /**
     * {@inheritdoc}
     */
    public function setReplace(array $replace)
    {
        $this->replace = $replace;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironments()
    {
        return $this->environments;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnvironments(array $environments)
    {
        $this->environments = $environments;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLoadAfter()
    {
        return $this->loadAfter;
    }

    /**
     * {@inheritdoc}
     */
    public function setLoadAfter(array $loadAfter)
    {
        $this->loadAfter = $loadAfter;

        return $this;
    }
}
