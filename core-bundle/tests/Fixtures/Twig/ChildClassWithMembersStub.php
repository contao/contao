<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures\Twig;

class ChildClassWithMembersStub extends ParentClassWithMembersStub
{
    final public const PUBLIC_CONSTANT = 3;

    protected const PROTECTED_CONSTANT = 2;

    private const PRIVATE_CONSTANT = 1;

    public string $publicField = 'c';

    public static string $publicStaticField = 'C';

    protected string $protectedField = 'b';

    protected static string $protectedStaticField = 'B';

    private string $privateField = 'a';

    private static string $privateStaticField = 'A';

    public function __construct()
    {
        /** @phpstan-ignore-next-line */
        $this->dynamic = 'd';

        parent::__construct();
    }

    public function __foo(): void
    {
        // should be ignored
    }

    public function publicDo(string $x = ''): string
    {
        return __FUNCTION__.$x;
    }

    public static function publicStaticDo(string $x = ''): string
    {
        return __FUNCTION__.$x;
    }

    protected function protectedDo(string $x = ''): string
    {
        return __FUNCTION__.$x;
    }

    protected static function protectedStaticDo(string $x = ''): string
    {
        return __FUNCTION__.$x;
    }

    private function privateDo(string $x = ''): string
    {
        return __FUNCTION__.$x;
    }

    private static function privateStaticDo(string $x = ''): string
    {
        return __FUNCTION__.$x;
    }
}
