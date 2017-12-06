<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Picker;

use Contao\CoreBundle\Picker\PickerConfig;
use PHPUnit\Framework\TestCase;

/**
 * Tests the PickerConfig class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PickerConfigTest extends TestCase
{
    /**
     * @var PickerConfig
     */
    protected $config;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->config = new PickerConfig('link', ['fieldType' => 'radio'], 'foo', 'alias');
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf('Contao\CoreBundle\Picker\PickerConfig', $this->config);
    }

    /**
     * Tests the getter methods.
     */
    public function testCanReadValues()
    {
        $this->assertSame('link', $this->config->getContext());
        $this->assertSame(['fieldType' => 'radio'], $this->config->getExtras());
        $this->assertSame('foo', $this->config->getValue());
        $this->assertSame('alias', $this->config->getCurrent());
    }

    /**
     * Tests the getExtra() and setExtra() methods.
     */
    public function testCanReadAndWriteExtras()
    {
        $this->assertSame('radio', $this->config->getExtra('fieldType'));
        $this->assertNull($this->config->getExtra('foo'));

        $this->config->setExtra('foo', 'bar');

        $this->assertSame('bar', $this->config->getExtra('foo'));
    }

    /**
     * Tests the cloneForCurrent() method.
     */
    public function testClonesTheCurrentObject()
    {
        $clone = $this->config->cloneForCurrent('new-alias');

        $this->assertNotSame($clone, $this->config);
        $this->assertSame('link', $clone->getContext());
        $this->assertSame(['fieldType' => 'radio'], $clone->getExtras());
        $this->assertSame('foo', $clone->getValue());
        $this->assertSame('new-alias', $clone->getCurrent());
    }

    /**
     * Tests the jsonSerialize() method.
     */
    public function testSerializesItselfToAnArray()
    {
        $this->assertSame(
            [
                'context' => 'link',
                'extras' => ['fieldType' => 'radio'],
                'current' => 'alias',
                'value' => 'foo',
            ],
            $this->config->jsonSerialize()
        );
    }

    /**
     * Tests the urlEncode() method.
     */
    public function testCreatesAnEncodedJsonString()
    {
        $data = json_encode([
            'context' => 'link',
            'extras' => ['fieldType' => 'radio'],
            'current' => 'alias',
            'value' => 'foo',
        ]);

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($data))) {
            $data = $encoded;
        }

        $this->assertSame(strtr(base64_encode($data), '+/=', '-_,'), $this->config->urlEncode());
    }

    /**
     * Tests the urlDecode() method.
     */
    public function testDecodesAnEncodedJsonString()
    {
        $data = json_encode([
            'context' => 'link',
            'extras' => ['fieldType' => 'radio'],
            'current' => 'alias',
            'value' => 'foo',
        ]);

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($data))) {
            $data = $encoded;
        }

        $config = $this->config->urlDecode(base64_encode(strtr($data, '-_,', '+/=')));

        $this->assertInstanceOf('Contao\CoreBundle\Picker\PickerConfig', $config);
        $this->assertSame('link', $config->getContext());
        $this->assertSame(['fieldType' => 'radio'], $config->getExtras());
        $this->assertSame('alias', $config->getCurrent());
        $this->assertSame('foo', $config->getValue());
    }

    /**
     * Tests the urlDecode() method with invalid JSON data.
     */
    public function testFailsToDecodeAnEncodedJsonStringIfTheJsonIsInvalid()
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
