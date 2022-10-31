<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\Database;
use Contao\PageModel;
use Contao\System;
use Contao\TestCase\FunctionalTestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;

class StatementTest extends FunctionalTestCase
{
    use ExpectDeprecationTrait;

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer(static::bootKernel()->getContainer());
    }

    /**
     * @group legacy
     */
    public function testExecuteStatementBackwardsCompatibility(): void
    {
        $db = Database::getInstance();

        $this->expectDeprecation('%sPassing more parameters than "?" tokens has been deprecated%s');

        $this->assertSame('1', (string) $db->prepare('SELECT 1')->execute(2)->first()->fetchField());
        $this->assertSame('1', (string) $db->prepare('SELECT 1')->execute([2])->first()->fetchField());
        $this->assertSame('1', (string) $db->prepare('SELECT ?')->execute(1, 2)->first()->fetchField());
        $this->assertSame('1', (string) $db->prepare('SELECT ?')->execute([1, 2])->first()->fetchField());
        $this->assertSame('1', (string) $db->prepare('SELECT ?')->execute(1, 2, 3, 4, 5, 6)->first()->fetchField());
        $this->assertSame('1', (string) $db->prepare('SELECT ?')->execute([1, 2, 3, 4, 5, 6])->first()->fetchField());

        $this->expectExceptionMessageMatches("/number of variables must match the number of parameters|number of bound variables does not match number of tokens|number of variables doesn't match number of parameters in prepared statement/i");

        $db->prepare('SELECT ?, ?, ?')->execute(1, 2)->fetchRow();
    }

    /**
     * @group legacy
     */
    public function testFindByNullBackwardsCompatibility(): void
    {
        $GLOBALS['TL_MODELS']['tl_page'] = PageModel::class;

        /** @phpstan-ignore-next-line */
        $this->assertNull(PageModel::findWithDetails(null));
        $this->assertNull(PageModel::findByPk(null));
        $this->assertNull(PageModel::findOneBy('id', null));
        $this->assertNull(PageModel::findBy('id', null));
        $this->assertNull(PageModel::findAll(['column' => 'id', 'value' => null]));

        unset($GLOBALS['TL_MODELS']);
    }
}
