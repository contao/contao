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
        $guidance = new TemplateGuidance()->templateGuidance();

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
    }

    public function testProvidesHtmlAttributesGuidanceForAvailableMethods(): void
    {
        $guidance = new TemplateGuidance()->htmlAttributes();

        $this->assertStringContainsString('mergeWith', $guidance);
        $this->assertStringContainsString('addClass', $guidance);
        $this->assertStringContainsString('setIfExists', $guidance);
        $this->assertStringContainsString('.mergeWith(accordion_header_attributes|default)', $guidance);
        $this->assertStringContainsString('.mergeWith(figure.options.attr|default)', $guidance);
        $this->assertStringContainsString('The order matters', $guidance);
        $this->assertStringContainsString('<img{{ image_attributes }}>', $guidance);
    }
}
