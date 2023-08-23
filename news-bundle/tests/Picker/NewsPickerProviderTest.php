<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\Picker;

use Contao\CoreBundle\Picker\PickerConfig;
use Contao\NewsArchiveModel;
use Contao\NewsBundle\Picker\NewsPickerProvider;
use Contao\NewsModel;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class NewsPickerProviderTest extends ContaoTestCase
{
    public function testCreatesTheMenuItem(): void
    {
        $config = json_encode([
            'context' => 'link',
            'extras' => [],
            'current' => 'newsPicker',
            'value' => '',
        ]);

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($config))) {
            $config = $encoded;
        }

        $picker = $this->getPicker();
        $item = $picker->createMenuItem(new PickerConfig('link', [], '', 'newsPicker'));
        $uri = 'contao_backend?do=news&popup=1&picker='.strtr(base64_encode($config), '+/=', '-_,');

        $this->assertSame('News picker', $item->getLabel());
        $this->assertSame(['class' => 'newsPicker'], $item->getLinkAttributes());
        $this->assertTrue($item->isCurrent());
        $this->assertSame($uri, $item->getUri());
    }

    public function testChecksIfAMenuItemIsCurrent(): void
    {
        $picker = $this->getPicker();

        $this->assertTrue($picker->isCurrent(new PickerConfig('link', [], '', 'newsPicker')));
        $this->assertFalse($picker->isCurrent(new PickerConfig('link', [], '', 'filePicker')));
    }

    public function testReturnsTheCorrectName(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('newsPicker', $picker->getName());
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

        $this->assertTrue($picker->supportsValue(new PickerConfig('link', [], '{{news_url::5}}')));
        $this->assertFalse($picker->supportsValue(new PickerConfig('link', [], '{{link_url::5}}')));
    }

    public function testReturnsTheDcaTable(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('tl_news', $picker->getDcaTable());
    }

    public function testReturnsTheDcaAttributes(): void
    {
        $picker = $this->getPicker();
        $extra = ['source' => 'tl_news.2'];

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'value' => '5',
                'flags' => ['urlattr'],
            ],
            $picker->getDcaAttributes(new PickerConfig('link', $extra, '{{news_url::5|urlattr}}')),
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

        $this->assertSame('{{news_url::5}}', $picker->convertDcaValue(new PickerConfig('link'), 5));
    }

    public function testConvertsTheDcaValueWithACustomInsertTag(): void
    {
        $picker = $this->getPicker();

        $this->assertSame(
            '{{news_title::5}}',
            $picker->convertDcaValue(new PickerConfig('link', ['insertTag' => '{{news_title::%s}}']), 5),
        );
    }

    public function testAddsTableAndIdIfThereIsAValue(): void
    {
        $model = $this->mockClassWithProperties(NewsArchiveModel::class);
        $model->id = 1;

        $news = $this->createMock(NewsModel::class);
        $news
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn($model)
        ;

        $config = new PickerConfig('link', [], '{{news_url::1}}', 'newsPicker');

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $news]),
        ];

        $picker = $this->getPicker();
        $picker->setFramework($this->mockContaoFramework($adapters));

        $method = new \ReflectionMethod(NewsPickerProvider::class, 'getRouteParameters');
        $params = $method->invokeArgs($picker, [$config]);

        $this->assertSame('news', $params['do']);
        $this->assertSame('tl_news', $params['table']);
        $this->assertSame(1, $params['id']);
    }

    public function testDoesNotAddTableAndIdIfThereIsNoEventsModel(): void
    {
        $config = new PickerConfig('link', [], '{{news_url::1}}', 'newsPicker');

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => null]),
        ];

        $picker = $this->getPicker();
        $picker->setFramework($this->mockContaoFramework($adapters));

        $method = new \ReflectionMethod(NewsPickerProvider::class, 'getRouteParameters');
        $params = $method->invokeArgs($picker, [$config]);

        $this->assertSame('news', $params['do']);
        $this->assertArrayNotHasKey('tl_news', $params);
        $this->assertArrayNotHasKey('id', $params);
    }

    public function testDoesNotAddTableAndIdIfThereIsNoModel(): void
    {
        $news = $this->createMock(NewsModel::class);
        $news
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn(null)
        ;

        $config = new PickerConfig('link', [], '{{news_url::1}}', 'newsPicker');

        $adapters = [
            NewsModel::class => $this->mockConfiguredAdapter(['findById' => $news]),
        ];

        $picker = $this->getPicker();
        $picker->setFramework($this->mockContaoFramework($adapters));

        $method = new \ReflectionMethod(NewsPickerProvider::class, 'getRouteParameters');
        $params = $method->invokeArgs($picker, [$config]);

        $this->assertSame('news', $params['do']);
        $this->assertArrayNotHasKey('tl_news', $params);
        $this->assertArrayNotHasKey('id', $params);
    }

    private function getPicker(bool|null $accessGranted = null): NewsPickerProvider
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
            ->willReturn('News picker')
        ;

        return new NewsPickerProvider($menuFactory, $router, $translator, $security);
    }
}
