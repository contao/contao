<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Api\UserTemplate;

use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\E2eTests\Api\ApiTestTrait;

class UserTemplateTest extends AbstractContaoMonorepoE2ETestCase
{
    use ApiTestTrait;

    private const string TEMPLATE = 'content_element/text';

    public function testListsThemes(): void
    {
        [$status] = $this->apiRequest('GET', '/contao/api/user_template_themes');

        $this->assertSame(200, $status);
    }

    public function testDiscoversTemplates(): void
    {
        [$status, $response] = $this->apiRequest('GET', '/contao/api/user_templates?query='.self::TEMPLATE);

        $this->assertSame(200, $status);
        $this->assertSame(self::TEMPLATE, $response['tree']['content_element']['text'][0]['identifier']);
    }

    public function testReadsATemplate(): void
    {
        [$status, $template] = $this->apiRequest('GET', '/contao/api/user_templates/'.self::TEMPLATE);

        $this->assertSame(200, $status);
        $this->assertSame(self::TEMPLATE, $template['identifier']);
        $this->assertNotEmpty($template['templates']);
    }

    public function testCreatesSavesAndDeletesAUserTemplate(): void
    {
        // User templates are files and survive between runs, so remove a leftover first
        $this->apiRequest('DELETE', '/contao/api/user_templates/'.self::TEMPLATE);

        [$status, $response] = $this->apiRequest('POST', '/contao/api/user_template_operations/create', ['name' => self::TEMPLATE]);

        $this->assertLessThan(300, $status, json_encode($response, JSON_PRETTY_PRINT));

        [, $template] = $this->apiRequest('GET', '/contao/api/user_templates/'.self::TEMPLATE);

        $this->assertTrue($template['can_edit']);

        $code = "{% extends '@Contao/content_element/text.html.twig' %}\n{# Saved via API #}\n";

        [$status, $response] = $this->apiRequest('PATCH', '/contao/api/user_templates/'.self::TEMPLATE, ['code' => $code]);

        $this->assertLessThan(300, $status, json_encode($response, JSON_PRETTY_PRINT));

        [, $template] = $this->apiRequest('GET', '/contao/api/user_templates/'.self::TEMPLATE);

        $this->assertStringContainsString('Saved via API', json_encode($template['templates']));

        [$status] = $this->apiRequest('DELETE', '/contao/api/user_templates/'.self::TEMPLATE);

        $this->assertLessThan(300, $status);

        [, $template] = $this->apiRequest('GET', '/contao/api/user_templates/'.self::TEMPLATE);

        $this->assertFalse($template['can_edit']);
    }
}
