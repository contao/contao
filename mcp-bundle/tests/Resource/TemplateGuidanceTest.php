<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tests\Resource;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\McpBundle\Resource\TemplateGuidance;
use PHPUnit\Framework\TestCase;

final class TemplateGuidanceTest extends TestCase
{
    public function testProvidesVersionSpecificTemplateGuidance(): void
    {
        $guidance = new TemplateGuidance(true)->templateGuidance();

        $this->assertStringContainsString(ContaoCoreBundle::getVersion(), $guidance);
        $this->assertStringContainsString('Analyze impact', $guidance);
        $this->assertStringContainsString('parent()', $guidance);
        $this->assertStringContainsString('scoped `{% with {…} %}`', $guidance);
        $this->assertStringContainsString("sanitize_html('contao')", $guidance);
        $this->assertStringContainsString("csp_nonce('script-src')", $guidance);
        $this->assertStringContainsString('administrator privileges (`ROLE_ADMIN`)', $guidance);
        $this->assertStringContainsString('Validation compiles the complete proposed source', $guidance);
        $this->assertStringContainsString('contao_template_snapshot', $guidance);
        $this->assertStringContainsString('contao_template_rollback', $guidance);
        $this->assertStringContainsString('contao_twig_environment_discover', $guidance);
        $this->assertStringContainsString('contao_twig_environment_inspect', $guidance);
        $this->assertStringContainsString('not current frontend or request values', $guidance);
        $this->assertStringContainsString('best-effort reflection metadata', $guidance);
    }

    public function testProvidesHtmlAttributesGuidanceForAvailableMethods(): void
    {
        // The html-attributes guidance is independent of Template Studio.
        $guidance = new TemplateGuidance(false)->htmlAttributes();

        $this->assertStringContainsString('mergeWith', $guidance);
        $this->assertStringContainsString('addClass', $guidance);
        $this->assertStringContainsString('setIfExists', $guidance);
        $this->assertStringContainsString('.mergeWith(accordion_header_attributes|default)', $guidance);
        $this->assertStringContainsString('.mergeWith(figure.options.attr|default)', $guidance);
        $this->assertStringContainsString('The order matters', $guidance);
        $this->assertStringContainsString('<img{{ image_attributes }}>', $guidance);
    }

    public function testExplainsThatTemplateStudioIsDisabled(): void
    {
        $guidance = new TemplateGuidance(false)->templateGuidance();

        $this->assertStringContainsString('Template Studio is disabled', $guidance);
        $this->assertStringNotContainsString('contao_template_list_themes', $guidance);
    }
}
