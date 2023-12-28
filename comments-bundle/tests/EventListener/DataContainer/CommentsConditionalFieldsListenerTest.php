<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CommentsBundle\Tests\EventListener\DataContainer;

use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\CommentsBundle\EventListener\DataContainer\CommentsConditionalFieldsListener;
use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Contao\FaqBundle\ContaoFaqBundle;
use Contao\NewsBundle\ContaoNewsBundle;
use Contao\TestCase\ContaoTestCase;

class CommentsConditionalFieldsListenerTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_LANG']);

        parent::tearDown();
    }

    public function testTableIsNotSet(): void
    {
        $GLOBALS['TL_DCA'] = [
            'tl_foo' => [],
            'tl_bar' => [],
        ];

        // Test will fail if tl_module is set due to palettes not existing
        $listener = new CommentsConditionalFieldsListener(['ContaoNewsBundle' => ContaoNewsBundle::class]);
        $listener('tl_module');

        $this->assertArrayNotHasKey('tl_module', $GLOBALS['TL_DCA']);
    }

    public function testAppliesModuleFields(): void
    {
        $palettes = '{foo_legend},bar;{protected_legend},baz';
        $expected = '{foo_legend},bar;{comment_legend:hide},com_template;{protected_legend},baz';

        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_module']['palettes'] = [
            'reader' => $palettes,
            'newsreader' => $palettes,
            'faqreader' => $palettes,
            'eventreader' => $palettes,
        ];

        $listener = new CommentsConditionalFieldsListener([
            'ContaoNewsBundle' => ContaoNewsBundle::class,
            'ContaoCalendarBundle' => ContaoCalendarBundle::class,
            'ContaoFaqBundle' => ContaoFaqBundle::class,
        ]);
        $listener('tl_module');

        $this->assertSame($expected, $GLOBALS['TL_DCA']['tl_module']['palettes']['newsreader']);
        $this->assertSame($expected, $GLOBALS['TL_DCA']['tl_module']['palettes']['faqreader']);
        $this->assertSame($expected, $GLOBALS['TL_DCA']['tl_module']['palettes']['eventreader']);
    }

    public function testIgnoresModuleFields(): void
    {
        $palettes = '{foo_legend},bar;{protected_legend},baz';

        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_module']['palettes']['fooreader'] = $palettes;

        $listener = new CommentsConditionalFieldsListener(['FooBundle' => ContaoModuleBundle::class]);
        $listener('tl_module');

        $this->assertSame($palettes, $GLOBALS['TL_DCA']['tl_module']['palettes']['fooreader']);
    }

    public function testAppliesNewsArchiveFields(): void
    {
        $this->testAppliesParentFields(['ContaoNewsBundle' => ContaoNewsBundle::class], 'tl_news_archive');
    }

    public function testAppliesCalendarFields(): void
    {
        $this->testAppliesParentFields(['ContaoCalendarBundle' => ContaoCalendarBundle::class], 'tl_calendar');
    }

    public function testAppliesFaqCategoryFields(): void
    {
        $this->testAppliesParentFields(['ContaoFaqBundle' => ContaoFaqBundle::class], 'tl_faq_category');
    }

    public function testAppliesNewsFields(): void
    {
        $this->testAppliesChildrenFields(['ContaoNewsBundle' => ContaoNewsBundle::class], 'tl_news', true);
    }

    public function testAppliesCalendarEventFields(): void
    {
        $this->testAppliesChildrenFields(['ContaoCalendarBundle' => ContaoCalendarBundle::class], 'tl_calendar_events', true);
    }

    public function testAppliesFaqFields(): void
    {
        $this->testAppliesChildrenFields(['ContaoFaqBundle' => ContaoFaqBundle::class], 'tl_faq');
    }

    private function testAppliesParentFields(array $bundles, string $table): void
    {
        $palettes = '{foo_legend},bar';
        $expected = '{foo_legend},bar;{comments_legend:hide},allowComments';
        $fields = 'allowComments,notify,sortOrder,perPage,moderate,bbcode,requireLogin,disableCaptcha';

        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA'][$table] = [
            'palettes' => [
                '__selector__' => [],
                'default' => $palettes,
            ],
            'subpalettes' => [],
            'fields' => [],
        ];

        $listener = new CommentsConditionalFieldsListener($bundles);
        $listener($table);

        $this->assertSame($expected, $GLOBALS['TL_DCA'][$table]['palettes']['default']);

        foreach (explode(',', $fields) as $field) {
            $this->assertArrayHasKey($field, $GLOBALS['TL_DCA'][$table]['fields']);
        }
    }

    private function testAppliesChildrenFields(array $bundles, string $table, bool $extended = false): void
    {
        $palettes = '{foo_legend},bar;{publish_legend},baz';
        $expected = '{foo_legend},bar;{expert_legend:hide},noComments;{publish_legend},baz';

        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA'][$table] = [
            'palettes' => [
                'default' => $palettes,
            ],
            'fields' => [],
            'sorting' => [
                'headerFields' => [],
            ],
        ];

        if ($extended) {
            $GLOBALS['TL_DCA'][$table]['palettes']['internal'] = $palettes;
            $GLOBALS['TL_DCA'][$table]['palettes']['article'] = $palettes;
            $GLOBALS['TL_DCA'][$table]['palettes']['external'] = $palettes;
        }

        $listener = new CommentsConditionalFieldsListener($bundles);
        $listener($table);

        $this->assertContains('allowComments', $GLOBALS['TL_DCA'][$table]['list']['sorting']['headerFields']);
        $this->assertSame($expected, $GLOBALS['TL_DCA'][$table]['palettes']['default']);

        if ($extended) {
            $this->assertSame($expected, $GLOBALS['TL_DCA'][$table]['palettes']['internal']);
            $this->assertSame($expected, $GLOBALS['TL_DCA'][$table]['palettes']['article']);
            $this->assertSame($expected, $GLOBALS['TL_DCA'][$table]['palettes']['external']);
        }
    }
}
