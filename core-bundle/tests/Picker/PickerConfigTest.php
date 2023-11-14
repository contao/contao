<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Picker;

use Contao\CoreBundle\Picker\PickerConfig;
use PHPUnit\Framework\TestCase;

class PickerConfigTest extends TestCase
{
    private PickerConfig $config;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new PickerConfig('link', ['fieldType' => 'radio'], 'foo', 'alias');
    }

    public function testCanReadValues(): void
    {
        $this->assertSame('link', $this->config->getContext());
        $this->assertSame(['fieldType' => 'radio'], $this->config->getExtras());
        $this->assertSame('foo', $this->config->getValue());
        $this->assertSame('alias', $this->config->getCurrent());
    }

    public function testCanReadAndWriteExtras(): void
    {
        $this->assertSame('radio', $this->config->getExtra('fieldType'));
        $this->assertNull($this->config->getExtra('foo'));

        $this->config->setExtra('foo', 'bar');

        $this->assertSame('bar', $this->config->getExtra('foo'));
    }

    public function testCanReadProviderExtras(): void
    {
        $this->config->setExtra('insertTag', '{{fallback}}');
        $this->config->setExtra('foobar', ['insertTag' => '{{foobarSpecific}}']);

        $this->assertSame('{{fallback}}', $this->config->getExtraForProvider('insertTag', 'notExistingProvider'));
        $this->assertSame('{{foobarSpecific}}', $this->config->getExtraForProvider('insertTag', 'foobar'));
    }

    public function testClonesTheCurrentObject(): void
    {
        $clone = $this->config->cloneForCurrent('new-alias');

        $this->assertNotSame($clone, $this->config);
        $this->assertSame('link', $clone->getContext());
        $this->assertSame(['fieldType' => 'radio'], $clone->getExtras());
        $this->assertSame('foo', $clone->getValue());
        $this->assertSame('new-alias', $clone->getCurrent());
    }

    public function testSerializesItselfToAnArray(): void
    {
        $this->assertSame(
            [
                'context' => 'link',
                'extras' => ['fieldType' => 'radio'],
                'current' => 'alias',
                'value' => 'foo',
            ],
            $this->config->jsonSerialize(),
        );
    }

    public function testCreatesAnEncodedJsonString(): void
    {
        $data = json_encode(
            [
                'context' => 'link',
                'extras' => ['fieldType' => 'radio'],
                'current' => 'alias',
                'value' => 'foo',
            ],
            JSON_THROW_ON_ERROR,
        );

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($data))) {
            $data = $encoded;
        }

        $this->assertSame(strtr(base64_encode($data), '+/=', '-_,'), $this->config->urlEncode());
    }

    public function testDecodesAnEncodedJsonString(): void
    {
        $data = json_encode(
            [
                'context' => 'link',
                'extras' => ['fieldType' => 'radio'],
                'current' => 'alias',
                'value' => 'foo',
            ],
            JSON_THROW_ON_ERROR,
        );

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($data))) {
            $data = $encoded;
        }

        $config = $this->config->urlDecode(base64_encode(strtr($data, '-_,', '+/=')));

        $this->assertSame('link', $config->getContext());
        $this->assertSame(['fieldType' => 'radio'], $config->getExtras());
        $this->assertSame('alias', $config->getCurrent());
        $this->assertSame('foo', $config->getValue());
    }

    public function testFailsToDecodeAnEncodedJsonStringIfTheJsonIsInvalid(): void
    {
        $data = '{"invalid';

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($data))) {
            $data = $encoded;
        }

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid JSON data');

        $this->config->urlDecode(base64_encode($data));
    }
}
