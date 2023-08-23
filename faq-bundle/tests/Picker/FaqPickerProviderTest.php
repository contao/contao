<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\FaqBundle\Tests\Picker;

use Contao\CoreBundle\Picker\PickerConfig;
use Contao\FaqBundle\Picker\FaqPickerProvider;
use Contao\FaqCategoryModel;
use Contao\FaqModel;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class FaqPickerProviderTest extends ContaoTestCase
{
    public function testCreatesTheMenuItem(): void
    {
        $config = json_encode([
            'context' => 'link',
            'extras' => [],
            'current' => 'faqPicker',
            'value' => '',
        ]);

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($config))) {
            $config = $encoded;
        }

        $picker = $this->getPicker();
        $item = $picker->createMenuItem(new PickerConfig('link', [], '', 'faqPicker'));
        $uri = 'contao_backend?do=faq&popup=1&picker='.urlencode(strtr(base64_encode($config), '+/=', '-_,'));

        $this->assertSame('Faq picker', $item->getLabel());
        $this->assertSame(['class' => 'faqPicker'], $item->getLinkAttributes());
        $this->assertTrue($item->isCurrent());
        $this->assertSame($uri, $item->getUri());
    }

    public function testChecksIfAMenuItemIsCurrent(): void
    {
        $picker = $this->getPicker();

        $this->assertTrue($picker->isCurrent(new PickerConfig('link', [], '', 'faqPicker')));
        $this->assertFalse($picker->isCurrent(new PickerConfig('link', [], '', 'filePicker')));
    }

    public function testReturnsTheCorrectName(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('faqPicker', $picker->getName());
    }

    public function testChecksIfAContextIsSupported(): void
    {
        $picker = $this->getPicker(true);

        $this->assertTrue($picker->supportsContext('link'));
        $this->assertFalse($picker->supportsContext('file'));
    }

    public function testChecksIfModuleAccessIsGranted(): void
    {
        $picker = $this->getPicker(false);

        $this->assertFalse($picker->supportsContext('link'));
    }

    public function testChecksIfAValueIsSupported(): void
    {
        $picker = $this->getPicker();

        $this->assertTrue($picker->supportsValue(new PickerConfig('link', [], '{{faq_url::5}}')));
        $this->assertFalse($picker->supportsValue(new PickerConfig('link', [], '{{link_url::5}}')));
    }

    public function testReturnsTheDcaTable(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('tl_faq', $picker->getDcaTable());
    }

    public function testReturnsTheDcaAttributes(): void
    {
        $picker = $this->getPicker();
        $extra = ['source' => 'tl_faq.2'];

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'value' => '5',
                'flags' => ['urlattr'],
            ],
            $picker->getDcaAttributes(new PickerConfig('link', $extra, '{{faq_url::5|urlattr}}')),
        );

        $this->assertSame(
            [
                'fieldType' => 'radio',
            ],
            $picker->getDcaAttributes(new PickerConfig('link', $extra, '{{link_url::5}}')),
        );
    }

    public function testConvertsTheDcaValue(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('{{faq_url::5}}', $picker->convertDcaValue(new PickerConfig('link'), 5));
    }

    public function testConvertsTheDcaValueWithACustomInsertTag(): void
    {
        $picker = $this->getPicker();

        $this->assertSame(
            '{{faq_title::5}}',
            $picker->convertDcaValue(new PickerConfig('link', ['insertTag' => '{{faq_title::%s}}']), 5),
        );
    }

    public function testAddsTableAndIdIfThereIsAValue(): void
    {
        $model = $this->mockClassWithProperties(FaqCategoryModel::class);
        $model->id = 1;

        $faq = $this->createMock(FaqModel::class);
        $faq
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn($model)
        ;

        $config = new PickerConfig('link', [], '{{faq_url::1}}', 'faqPicker');

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faq]),
        ];

        $picker = $this->getPicker();
        $picker->setFramework($this->mockContaoFramework($adapters));

        $method = new \ReflectionMethod(FaqPickerProvider::class, 'getRouteParameters');
        $params = $method->invokeArgs($picker, [$config]);

        $this->assertSame('faq', $params['do']);
        $this->assertSame('tl_faq', $params['table']);
        $this->assertSame(1, $params['id']);
    }

    public function testDoesNotAddTableAndIdIfThereIsNoEventsModel(): void
    {
        $config = new PickerConfig('link', [], '{{faq_url::1}}', 'faqPicker');

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => null]),
        ];

        $picker = $this->getPicker();
        $picker->setFramework($this->mockContaoFramework($adapters));

        $method = new \ReflectionMethod(FaqPickerProvider::class, 'getRouteParameters');
        $params = $method->invokeArgs($picker, [$config]);

        $this->assertSame('faq', $params['do']);
        $this->assertArrayNotHasKey('tl_faq', $params);
        $this->assertArrayNotHasKey('id', $params);
    }

    public function testDoesNotAddTableAndIdIfThereIsNoCalendarModel(): void
    {
        $faq = $this->createMock(FaqModel::class);
        $faq
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn(null)
        ;

        $config = new PickerConfig('link', [], '{{faq_url::1}}', 'faqPicker');

        $adapters = [
            FaqModel::class => $this->mockConfiguredAdapter(['findById' => $faq]),
        ];

        $picker = $this->getPicker();
        $picker->setFramework($this->mockContaoFramework($adapters));

        $method = new \ReflectionMethod(FaqPickerProvider::class, 'getRouteParameters');
        $params = $method->invokeArgs($picker, [$config]);

        $this->assertSame('faq', $params['do']);
        $this->assertArrayNotHasKey('tl_faq', $params);
        $this->assertArrayNotHasKey('id', $params);
    }

    private function getPicker(bool|null $accessGranted = null): FaqPickerProvider
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects(null === $accessGranted ? $this->never() : $this->once())
            ->method('isGranted')
            ->willReturn($accessGranted ?? false)
        ;

        $menuFactory = $this->createMock(FactoryInterface::class);
        $menuFactory
            ->method('createItem')
            ->willReturnCallback(
                static function (string $name, array $data) use ($menuFactory): ItemInterface {
                    $item = new MenuItem($name, $menuFactory);
                    $item->setLabel($data['label']);
                    $item->setLinkAttributes($data['linkAttributes']);
                    $item->setCurrent($data['current']);
                    $item->setUri($data['uri']);

                    return $item;
                },
            )
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(static fn (string $name, array $params): string => $name.'?'.http_build_query($params))
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturn('Faq picker')
        ;

        return new FaqPickerProvider($menuFactory, $router, $translator, $security);
    }
}
