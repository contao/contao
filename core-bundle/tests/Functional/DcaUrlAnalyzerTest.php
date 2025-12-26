<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\System;
use Contao\TestCase\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class DcaUrlAnalyzerTest extends FunctionalTestCase
{
    private static string|null $lastImport = null;

    #[DataProvider('getCurrentTableId')]
    public function testGetCurrentTableId(string $url, array $expected): void
    {
        $container = self::createClient()->getContainer();
        System::setContainer($container);

        $container->get('request_stack')->push(Request::create("https://example.com/contao?$url"));

        $container->set(
            'security.authorization_checker',
            new class() implements AuthorizationCheckerInterface {
                public function isGranted(mixed $attribute, mixed $subject = null): bool
                {
                    return true;
                }
            },
        );

        $this->loadFixtureFile('default');

        $this->assertSame($expected, $container->get('contao.data_container.dca_url_analyzer')->getCurrentTableId());
    }

    public static function getCurrentTableId(): iterable
    {
        yield [
            'do=article&act=edit&id=1',
            ['tl_article', 1],
        ];

        yield [
            'do=article',
            ['tl_article', null],
        ];

        yield [
            'do=article&act=select',
            ['tl_article', null],
        ];

        yield [
            'do=article&act=show&id=1&popup=1',
            ['tl_article', 1],
        ];

        yield [
            'do=article&table=tl_content&id=1',
            ['tl_article', 1],
        ];

        yield [
            'do=article&id=1&table=tl_content&act=edit',
            ['tl_content', 1],
        ];

        yield [
            'do=article&id=1&table=tl_content&act=show&popup=1',
            ['tl_content', 1],
        ];

        yield [
            'do=article&id=1&table=tl_content&ptable=tl_content',
            ['tl_content', 1],
        ];

        yield [
            'do=article&id=2&ptable=tl_content&table=tl_content&act=edit',
            ['tl_content', 2],
        ];

        yield [
            'do=article&id=1&ptable=tl_content&table=tl_content&act=select',
            ['tl_content', 1],
        ];

        yield [
            'do=article&id=1&table=tl_content&act=select',
            ['tl_article', 1],
        ];

        yield [
            'do=article&id=1&ptable=tl_content&table=tl_content&act=editAll',
            ['tl_content', 1],
        ];

        yield [
            'do=article&id=1&table=tl_content&act=editAll',
            ['tl_article', 1],
        ];

        yield [
            'do=article&id=1&table=tl_content&act=paste&mode=copy',
            ['tl_article', 1],
        ];

        yield [
            'do=article&id=2&table=tl_content&act=paste&mode=copy&ptable=tl_content',
            ['tl_content', 1],
        ];

        yield [
            'do=themes',
            ['tl_theme', null],
        ];

        yield [
            'do=themes&act=select',
            ['tl_theme', null],
        ];

        yield [
            'do=themes&act=something',
            ['tl_theme', null],
        ];

        yield [
            'do=themes&table=tl_image_size&id=1',
            ['tl_theme', 1],
        ];

        yield [
            'do=themes&id=123&table=tl_image_size_item',
            ['tl_image_size', 123],
        ];

        yield [
            'do=themes&id=123&table=tl_image_size_item&act=edit',
            ['tl_image_size_item', 123],
        ];
    }

    #[DataProvider('getTrail')]
    public function testGetTrail(string $url, array $expected): void
    {
        $container = self::createClient()->getContainer();
        System::setContainer($container);

        $container->get('request_stack')->push(Request::create("https://example.com/contao?$url"));

        $container->set(
            'security.authorization_checker',
            new class() implements AuthorizationCheckerInterface {
                public function isGranted(mixed $attribute, mixed $subject = null): bool
                {
                    return true;
                }
            },
        );

        $this->loadFixtureFile('default');

        $this->assertSame($expected, $container->get('contao.data_container.dca_url_analyzer')->getTrail(withTreeTrail: true));
    }

    public static function getTrail(): iterable
    {
        yield [
            'do=article&act=edit&id=1',
            [
                ['url' => '/contao?do=article&table=tl_article', 'label' => 'Articles', 'treeTrail' => null, 'treeSiblings' => null],
                [
                    'url' => '/contao?do=article&id=1&table=tl_article&act=edit',
                    'label' => 'Article 1',
                    'treeTrail' => [
                        [
                            'url' => '/contao?do=article&table=tl_article&pn=1',
                            'label' => 'Edit page ID 1',
                        ],
                    ],
                    'treeSiblings' => [
                        [
                            'url' => '/contao?do=article&id=1&table=tl_article&act=edit',
                            'label' => 'Article 1',
                            'active' => true,
                        ],
                    ],
                ],
            ],
        ];

        yield [
            'do=article',
            [
                ['url' => '/contao?do=article&table=tl_article', 'label' => 'Articles', 'treeTrail' => null, 'treeSiblings' => null],
            ],
        ];

        yield [
            'do=article&act=select',
            [
                ['url' => '/contao?do=article&table=tl_article', 'label' => 'Articles', 'treeTrail' => null, 'treeSiblings' => null],
            ],
        ];

        yield [
            'do=article&act=show&id=1&popup=1',
            [
                ['url' => '/contao?do=article&table=tl_article', 'label' => 'Articles', 'treeTrail' => null, 'treeSiblings' => null],
                [
                    'url' => '/contao?do=article&id=1&table=tl_article&act=show',
                    'label' => 'Article 1',
                    'treeTrail' => [
                        [
                            'url' => '/contao?do=article&table=tl_article&pn=1',
                            'label' => 'Edit page ID 1',
                        ],
                    ],
                    'treeSiblings' => [
                        [
                            'url' => '/contao?do=article&id=1&table=tl_article&act=show',
                            'label' => 'Article 1',
                            'active' => true,
                        ],
                    ],
                ],
            ],
        ];

        yield [
            'do=article&table=tl_content&id=1',
            [
                ['url' => '/contao?do=article&table=tl_article', 'label' => 'Articles', 'treeTrail' => null, 'treeSiblings' => null],
                [
                    'url' => '/contao?do=article&id=1&table=tl_content',
                    'label' => 'Article 1',
                    'treeTrail' => [
                        [
                            'url' => '/contao?do=article&table=tl_article&pn=1',
                            'label' => 'Edit page ID 1',
                        ],
                    ],
                    'treeSiblings' => [
                        [
                            'url' => '/contao?do=article&id=1&table=tl_content',
                            'label' => 'Article 1',
                            'active' => true,
                        ],
                    ],
                ],
            ],
        ];

        yield [
            'do=article&id=1&table=tl_content&act=edit',
            [
                ['url' => '/contao?do=article&table=tl_article', 'label' => 'Articles', 'treeTrail' => null, 'treeSiblings' => null],
                [
                    'url' => '/contao?do=article&id=1&table=tl_content',
                    'label' => 'Article 1',
                    'treeTrail' => [
                        [
                            'url' => '/contao?do=article&table=tl_article&pn=1',
                            'label' => 'Edit page ID 1',
                        ],
                    ],
                    'treeSiblings' => [
                        [
                            'url' => '/contao?do=article&id=1&table=tl_content',
                            'label' => 'Article 1',
                            'active' => true,
                        ],
                    ],
                ],
                ['url' => '/contao?do=article&id=1&table=tl_content&ptable=tl_content&act=edit', 'label' => 'Element group', 'treeTrail' => null, 'treeSiblings' => null],
            ],
        ];

        yield [
            'do=article&id=1&table=tl_content&act=show&popup=1',
            [
                ['url' => '/contao?do=article&table=tl_article', 'label' => 'Articles', 'treeTrail' => null, 'treeSiblings' => null],
                [
                    'url' => '/contao?do=article&id=1&table=tl_content',
                    'label' => 'Article 1',
                    'treeTrail' => [
                        [
                            'url' => '/contao?do=article&table=tl_article&pn=1',
                            'label' => 'Edit page ID 1',
                        ],
                    ],
                    'treeSiblings' => [
                        [
                            'url' => '/contao?do=article&id=1&table=tl_content',
                            'label' => 'Article 1',
                            'active' => true,
                        ],
                    ],
                ],
                ['url' => '/contao?do=article&id=1&table=tl_content&ptable=tl_content&act=show', 'label' => 'Element group', 'treeTrail' => null, 'treeSiblings' => null],
            ],
        ];

        yield [
            'do=article&id=1&table=tl_content&ptable=tl_content',
            [
                ['url' => '/contao?do=article&table=tl_article', 'label' => 'Articles', 'treeTrail' => null, 'treeSiblings' => null],
                [
                    'url' => '/contao?do=article&id=1&table=tl_content',
                    'label' => 'Article 1',
                    'treeTrail' => [
                        [
                            'url' => '/contao?do=article&table=tl_article&pn=1',
                            'label' => 'Edit page ID 1',
                        ],
                    ],
                    'treeSiblings' => [
                        [
                            'url' => '/contao?do=article&id=1&table=tl_content',
                            'label' => 'Article 1',
                            'active' => true,
                        ],
                    ],
                ],
                ['url' => '/contao?do=article&id=1&table=tl_content&ptable=tl_content', 'label' => 'Element group', 'treeTrail' => null, 'treeSiblings' => null],
            ],
        ];

        yield [
            'do=article&id=2&ptable=tl_content&table=tl_content&act=edit',
            [
                ['url' => '/contao?do=article&table=tl_article', 'label' => 'Articles', 'treeTrail' => null, 'treeSiblings' => null],
                [
                    'url' => '/contao?do=article&id=1&table=tl_content',
                    'label' => 'Article 1',
                    'treeTrail' => [
                        [
                            'url' => '/contao?do=article&table=tl_article&pn=1',
                            'label' => 'Edit page ID 1',
                        ],
                    ],
                    'treeSiblings' => [
                        [
                            'url' => '/contao?do=article&id=1&table=tl_content',
                            'label' => 'Article 1',
                            'active' => true,
                        ],
                    ],
                ],
                ['url' => '/contao?do=article&id=1&table=tl_content&ptable=tl_content', 'label' => 'Element group', 'treeTrail' => null, 'treeSiblings' => null],
                ['url' => '/contao?do=article&id=2&table=tl_content&ptable=tl_content&act=edit', 'label' => 'Element group', 'treeTrail' => null, 'treeSiblings' => null],
            ],
        ];

        yield [
            'do=article&id=3&ptable=tl_content&table=tl_content&act=edit',
            [
                ['url' => '/contao?do=article&table=tl_article', 'label' => 'Articles', 'treeTrail' => null, 'treeSiblings' => null],
                [
                    'url' => '/contao?do=article&id=1&table=tl_content',
                    'label' => 'Article 1',
                    'treeTrail' => [
                        [
                            'url' => '/contao?do=article&table=tl_article&pn=1',
                            'label' => 'Edit page ID 1',
                        ],
                    ],
                    'treeSiblings' => [
                        [
                            'url' => '/contao?do=article&id=1&table=tl_content',
                            'label' => 'Article 1',
                            'active' => true,
                        ],
                    ],
                ],
                ['url' => '/contao?do=article&id=1&table=tl_content&ptable=tl_content', 'label' => 'Element group', 'treeTrail' => null, 'treeSiblings' => null],
                ['url' => '/contao?do=article&id=2&table=tl_content&ptable=tl_content', 'label' => 'Element group', 'treeTrail' => null, 'treeSiblings' => null],
                ['url' => '/contao?do=article&id=3&table=tl_content&ptable=tl_content&act=edit', 'label' => 'Headline', 'treeTrail' => null, 'treeSiblings' => null],
            ],
        ];

        yield [
            'do=themes&table=tl_image_size&id=1',
            [
                ['url' => '/contao?do=themes&table=tl_theme', 'label' => 'Themes', 'treeTrail' => null, 'treeSiblings' => null],
                ['url' => '/contao?do=themes&id=1&table=tl_image_size', 'label' => 'Default Theme', 'treeTrail' => null, 'treeSiblings' => null],
            ],
        ];

        yield [
            'do=themes&id=1&table=tl_layout',
            [
                ['url' => '/contao?do=themes&table=tl_theme', 'label' => 'Themes', 'treeTrail' => null, 'treeSiblings' => null],
                ['url' => '/contao?do=themes&id=1&table=tl_layout', 'label' => 'Default Theme', 'treeTrail' => null, 'treeSiblings' => null],
            ],
        ];

        yield [
            'do=themes&id=1&table=tl_layout&act=edit',
            [
                ['url' => '/contao?do=themes&table=tl_theme', 'label' => 'Themes', 'treeTrail' => null, 'treeSiblings' => null],
                ['url' => '/contao?do=themes&id=1&table=tl_layout', 'label' => 'Default Theme', 'treeTrail' => null, 'treeSiblings' => null],
                ['url' => '/contao?do=themes&id=1&table=tl_layout&act=edit', 'label' => 'Default Layout', 'treeTrail' => null, 'treeSiblings' => null],
            ],
        ];
    }

    private function loadFixtureFile(string $fileName): void
    {
        if (self::$lastImport === $fileName) {
            return;
        }

        self::$lastImport = $fileName;

        static::loadFixtures([__DIR__."/../Fixtures/Functional/DcaUrlAnalyzer/$fileName.yaml"]);
    }
}
