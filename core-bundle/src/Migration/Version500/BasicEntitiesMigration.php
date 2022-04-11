<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version500;

/**
 * @internal
 */
class BasicEntitiesMigration extends AbstractBasicEntitiesMigration
{
    protected function getDatabaseColumns(): array
    {
        return [
            ['tl_article', 'title'],
            ['tl_article', 'teaser'],

            ['tl_calendar', 'title'],

            ['tl_calendar_events', 'title'],
            ['tl_calendar_events', 'location'],
            ['tl_calendar_events', 'teaser'],
            ['tl_calendar_events', 'alt'],
            ['tl_calendar_events', 'imageUrl'],
            ['tl_calendar_events', 'caption'],
            ['tl_calendar_events', 'url'],
            ['tl_calendar_events', 'imageTitle'],
            ['tl_calendar_events', 'address'],
            ['tl_calendar_events', 'description'],
            ['tl_calendar_events', 'pageTitle'],

            ['tl_calendar_feed', 'title'],
            ['tl_calendar_feed', 'description'],

            ['tl_comments', 'comment'],

            ['tl_content', 'headline'],
            ['tl_content', 'text'],
            ['tl_content', 'alt'],
            ['tl_content', 'imageTitle'],
            ['tl_content', 'imageUrl'],
            ['tl_content', 'caption'],
            ['tl_content', 'html'],
            ['tl_content', 'listItems'],
            ['tl_content', 'tableItems'],
            ['tl_content', 'summary'],
            ['tl_content', 'mooHeadline'],
            ['tl_content', 'code'],
            ['tl_content', 'url'],
            ['tl_content', 'titleText'],
            ['tl_content', 'linkTitle'],
            ['tl_content', 'embed'],
            ['tl_content', 'data'],

            ['tl_faq', 'question'],
            ['tl_faq', 'answer'],
            ['tl_faq', 'alt'],
            ['tl_faq', 'imageUrl'],
            ['tl_faq', 'caption'],
            ['tl_faq', 'imageTitle'],
            ['tl_faq', 'description'],
            ['tl_faq', 'pageTitle'],

            ['tl_faq_category', 'title'],
            ['tl_faq_category', 'headline'],

            ['tl_files', 'meta'],

            ['tl_form_field', 'label'],
            ['tl_form_field', 'text'],
            ['tl_form_field', 'html'],
            ['tl_form_field', 'options'],
            ['tl_form_field', 'placeholder'],
            ['tl_form_field', 'value'],
            ['tl_form_field', 'errorMsg'],

            ['tl_layout', 'titleTag'],
            ['tl_layout', 'head'],

            ['tl_member', 'firstname'],
            ['tl_member', 'lastname'],
            ['tl_member', 'company'],
            ['tl_member', 'street'],
            ['tl_member', 'city'],
            ['tl_member', 'state'],
            ['tl_member', 'website'],

            ['tl_module', 'headline'],
            ['tl_module', 'customLabel'],
            ['tl_module', 'html'],
            ['tl_module', 'data'],
            ['tl_module', 'nl_text'],

            ['tl_news', 'headline'],
            ['tl_news', 'subheadline'],
            ['tl_news', 'teaser'],
            ['tl_news', 'alt'],
            ['tl_news', 'imageUrl'],
            ['tl_news', 'caption'],
            ['tl_news', 'url'],
            ['tl_news', 'imageTitle'],
            ['tl_news', 'description'],
            ['tl_news', 'pageTitle'],

            ['tl_news_archive', 'title'],

            ['tl_page', 'title'],
            ['tl_page', 'pageTitle'],
            ['tl_page', 'description'],
            ['tl_page', 'url'],
        ];
    }
}
