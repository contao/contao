<?php

namespace Foo\Dependend;

use Contao\ManagerBundle\ContaoManager\Dependency\DependentPluginInterface;

class FooDependendPlugin implements DependentPluginInterface
{
    /**
     * Gets list of Composer packages names that must be loaded before this plugin.
     *
     * @return string[]
     */
    public function getPackageDependencies()
    {
        return ['foo/bar-bundle'];
    }
}
