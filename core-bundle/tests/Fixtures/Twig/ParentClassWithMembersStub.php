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

class ParentClassWithMembersStub
{
    final public const PARENT_PUBLIC_CONSTANT = 3;

    protected const PARENT_PROTECTED_CONSTANT = 2;

    private const PARENT_PRIVATE_CONSTANT = 1;

    public string $parentPublicField = 'c';

    public static string $parentPublicStaticField = 'C';

    protected string $parentProtectedField = 'b';

    protected static string $parentProtectedStaticField = 'B';

    private string $parentPrivateField = 'a';

    private static string $parentPrivateStaticField = 'A';

    public function __construct()
    {
        /** @phpstan-ignore-next-line */
        $this->parentDynamic = 'd';
    }

    public function parentPublicDo(string $x = ''): string
    {
        return __FUNCTION__.$x;
    }

    public static function parentPublicStaticDo(string $x = ''): string
    {
        return __FUNCTION__.$x;
    }

    protected function parentProtectedDo(string $x = ''): string
    {
        return __FUNCTION__.$x;
    }

    protected static function parentProtectedStaticDo(string $x = ''): string
    {
        return __FUNCTION__.$x;
    }

    private function parentPrivateDo(string $x = ''): string
    {
        return __FUNCTION__.$x;
    }

    private static function parentPrivateStaticDo(string $x = ''): string
    {
        return __FUNCTION__.$x;
    }
}
