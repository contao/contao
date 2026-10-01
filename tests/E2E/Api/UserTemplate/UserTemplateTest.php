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
use PHPUnit\Framework\Attributes\DataProvider;

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

    #[DataProvider('getVariantTypes')]
    public function testSuggestsAVariantNameWithoutCreatingIt(string $prefix, string $base): void
    {
        // Without an identifier fragment, the operation only returns the suggestion
        [$status, $response] = $this->apiRequest('POST', '/contao/api/user_template_operations/create_'.$prefix.'_variant', ['name' => $base]);

        $this->assertLessThan(300, $status, json_encode($response, JSON_PRETTY_PRINT));
        $this->assertSame('create', $response['operation_type']);
        $this->assertSame($base, $response['identifier']);
        $this->assertNotEmpty($response['suggested_identifier_fragment']);
        $this->assertArrayHasKey('allowed_identifier_fragment_pattern', $response);
    }

    #[DataProvider('getVariantTypes')]
    public function testCreatesRenamesAndDeletesAVariant(string $prefix, string $base): void
    {
        // Variants are files and survive between runs, so use unique names
        $fragment = 'e2e_'.bin2hex(random_bytes(4));
        $variant = $this->createVariant($prefix, $base, $fragment);

        [, $template] = $this->apiRequest('GET', '/contao/api/user_templates/'.$variant);

        $this->assertSame($variant, $template['identifier']);
        $this->assertTrue($template['can_edit']);

        $renamed = $this->renameVariant($prefix, $variant, $fragment.'_renamed');

        [$status] = $this->apiRequest('GET', '/contao/api/user_templates/'.$variant);

        $this->assertSame(404, $status);

        [$status] = $this->apiRequest('DELETE', '/contao/api/user_templates/'.$renamed);

        $this->assertLessThan(300, $status);

        [$status] = $this->apiRequest('GET', '/contao/api/user_templates/'.$renamed);

        $this->assertSame(404, $status);
    }

    public static function getVariantTypes(): iterable
    {
        yield 'content element' => ['content_element', self::TEMPLATE];
        yield 'frontend module' => ['frontend_module', 'frontend_module/unfiltered_html'];
        yield 'page' => ['page', 'page/layout'];
    }

    /**
     * Renaming a variant updates the records that use it (tl_content.customTpl,
     * tl_module.customTpl and tl_layout.template).
     */
    #[DataProvider('getVariantUsages')]
    public function testRenamesAVariantAndUpdatesItsUsages(string $prefix, string $base, string $collection, array $record, string $field): void
    {
        $fragment = 'e2e_'.bin2hex(random_bytes(4));
        $variant = $this->createVariant($prefix, $base, $fragment);

        [$status, $created] = $this->apiRequest('POST', $this->interpolate($collection), $record + [$field => $variant]);

        $this->assertSame(201, $status, json_encode($created, JSON_PRETTY_PRINT));

        $renamed = $this->renameVariant($prefix, $variant, $fragment.'_renamed');

        [, $created] = $this->apiRequest('GET', $created['@id']);

        $this->assertSame($renamed, $created[$field]);

        $this->apiRequest('DELETE', '/contao/api/user_templates/'.$renamed);
    }

    public static function getVariantUsages(): iterable
    {
        yield 'content element' => ['content_element', self::TEMPLATE, '/contao/api/dc/article/{article}/content', ['type' => 'text', 'text' => '<p>Uses a variant</p>'], 'customTpl'];
        yield 'frontend module' => ['frontend_module', 'frontend_module/unfiltered_html', '/contao/api/dc/theme/{theme_editorial}/module', ['type' => 'unfiltered_html', 'name' => 'Uses a variant'], 'customTpl'];
        yield 'page' => ['page', 'page/layout', '/contao/api/dc/theme/{theme_editorial}/layout', ['type' => 'modern', 'name' => 'Uses a variant'], 'template'];
    }

    private function createVariant(string $prefix, string $base, string $fragment): string
    {
        [$status, $response] = $this->apiRequest(
            'POST',
            '/contao/api/user_template_operations/create_'.$prefix.'_variant', [
                'name' => $base,
                'parameters' => ['identifier_fragment' => $fragment],
        ],
        );

        $this->assertLessThan(300, $status, json_encode($response, JSON_PRETTY_PRINT));
        $this->assertSame($base.'/'.$fragment, $response['identifier']);

        return $response['identifier'];
    }

    private function renameVariant(string $prefix, string $variant, string $fragment): string
    {
        [$status, $response] = $this->apiRequest(
            'POST',
            '/contao/api/user_template_operations/rename_'.$prefix.'_variant', [
                'name' => $variant,
                'parameters' => ['identifier_fragment' => $fragment],
        ],
        );

        $this->assertLessThan(300, $status, json_encode($response, JSON_PRETTY_PRINT));
        $this->assertSame($variant, $response['old_identifier']);
        $this->assertSame(\dirname($variant).'/'.$fragment, $response['new_identifier']);

        return $response['new_identifier'];
    }
}
