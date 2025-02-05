<?php

namespace Contao\CoreBundle\Tests\Fixtures\Helper;

class HookHelper
{
    public static function registerHook(string $hook, \Closure $handler): void
    {
        $GLOBALS['TL_HOOKS'][$hook][] = [
            new class($handler) {
                public function __construct(private readonly \Closure $handler)
                {
                }

                public function __invoke(...$args)
                {
                    return ($this->handler)(...$args);
                }
            },
            '__invoke'
        ];
    }
}
