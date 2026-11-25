<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tests\UserTemplate;

use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\McpBundle\UserTemplate\UserTemplateValidator;
use Mcp\Exception\ToolCallException;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class UserTemplateValidatorTest extends TestCase
{
    public function testValidatesWithoutRenderingOrSaving(): void
    {
        $this->assertSame(
            ['identifier' => 'content_element/text', 'valid' => true, 'errors' => []],
            $this->createValidator()->validate('content_element/text', '{% block content %}Text{% endblock %}', 'demo'),
        );
    }

    public function testReturnsStructuredSyntaxErrors(): void
    {
        $result = $this->createValidator()->validate('content_element/text', '{% if %}', null);

        $this->assertFalse($result['valid']);
        $this->assertSame('content_element/text', $result['identifier']);
        $this->assertSame(1, $result['errors'][0]['line']);
        $this->assertNotSame('', $result['errors'][0]['message']);
    }

    public function testRejectsUnknownTemplateIdentifiers(): void
    {
        $loader = $this->createStub(ContaoFilesystemLoader::class);
        $loader
            ->method('getFirst')
            ->willThrowException(new \LogicException('Unknown template.'))
        ;

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Unknown template.');

        new UserTemplateValidator(new Environment(new ArrayLoader()), $loader)->validate('unknown', '', null);
    }

    private function createValidator(): UserTemplateValidator
    {
        $loader = $this->createStub(ContaoFilesystemLoader::class);
        $loader
            ->method('getFirst')
            ->willReturn('@Contao_Test/content_element/text.html.twig')
        ;

        return new UserTemplateValidator(new Environment(new ArrayLoader()), $loader);
    }
}
