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

use Contao\CoreBundle\Picker\ArticlePickerProvider;
use Contao\CoreBundle\Picker\PickerConfig;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

class ArticlePickerProviderTest extends ContaoTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $GLOBALS['TL_LANG']['MSC']['articlePicker'] = 'Article picker';
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        unset($GLOBALS['TL_LANG']);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using a picker provider without injecting the translator service has been deprecated %s.
     */
    public function testCreatesTheMenuItem(): void
    {
        $config = json_encode([
            'context' => 'link',
            'extras' => [],
            'current' => 'articlePicker',
            'value' => '',
        ]);

        if (\function_exists('gzencode') && false !== ($encoded = @gzencode($config))) {
            $config = $encoded;
        }

        $picker = $this->getPicker();
        $item = $picker->createMenuItem(new PickerConfig('link', [], '', 'articlePicker'));
        $uri = 'contao_backend?do=article&popup=1&picker='.strtr(base64_encode($config), '+/=', '-_,');

        $this->assertSame('Article picker', $item->getLabel());
        $this->assertSame(['class' => 'articlePicker'], $item->getLinkAttributes());
        $this->assertTrue($item->isCurrent());
        $this->assertSame($uri, $item->getUri());
    }

    public function testChecksIfAMenuItemIsCurrent(): void
    {
        $picker = $this->getPicker();

        $this->assertTrue($picker->isCurrent(new PickerConfig('link', [], '', 'articlePicker')));
        $this->assertFalse($picker->isCurrent(new PickerConfig('link', [], '', 'filePicker')));
    }

    public function testReturnsTheCorrectName(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('articlePicker', $picker->getName());
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

        $this->assertTrue($picker->supportsValue(new PickerConfig('link', [], '{{article_url::5}}')));
        $this->assertFalse($picker->supportsValue(new PickerConfig('link', [], '{{link_url::5}}')));
    }

    public function testReturnsTheDcaTable(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('tl_article', $picker->getDcaTable());
    }

    public function testReturnsTheDcaAttributes(): void
    {
        $picker = $this->getPicker();
        $extra = ['source' => 'tl_article.2'];

        $this->assertSame(
            [
                'fieldType' => 'radio',
                'value' => '5',
                'flags' => ['urlattr'],
            ],
            $picker->getDcaAttributes(new PickerConfig('link', $extra, '{{article_url::5|urlattr}}'))
        );

        $this->assertSame(
            [
                'fieldType' => 'radio',
            ],
            $picker->getDcaAttributes(new PickerConfig('link', $extra, '{{link_url::5}}'))
        );
    }

    public function testConvertsTheDcaValue(): void
    {
        $picker = $this->getPicker();

        $this->assertSame('{{article_url::5}}', $picker->convertDcaValue(new PickerConfig('link'), 5));
    }

    public function testConvertsTheDcaValueWithACustomInsertTag(): void
    {
        $picker = $this->getPicker();

        $this->assertSame(
            '{{article_title::5}}',
            $picker->convertDcaValue(new PickerConfig('link', ['insertTag' => '{{article_title::%s}}']), 5)
        );
    }

    private function getPicker(bool $accessGranted = null): ArticlePickerProvider
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
                }
            )
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(
                static function (string $name, array $params): string {
                    return $name.'?'.http_build_query($params);
                }
            )
        ;

        return new ArticlePickerProvider($menuFactory, $router, null, $security);
    }
}
