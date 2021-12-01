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
    public const PARENT_PUBLIC_CONSTANT = 3;
    protected const PARENT_PROTECTED_CONSTANT = 2;
    private const PARENT_PRIVATE_CONSTANT = 1;

    public $parentPublicField = 'c';
    public static $parentPublicStaticField = 'C';
    protected $parentProtectedField = 'b';
    protected static $parentProtectedStaticField = 'B';

    private $parentPrivateField = 'a';
    private static $parentPrivateStaticField = 'A';

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
