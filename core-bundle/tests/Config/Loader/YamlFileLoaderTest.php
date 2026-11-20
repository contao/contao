<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Config\Loader;

use Contao\CoreBundle\Config\Loader\YamlFileLoader;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Translation\Exception\InvalidResourceException;

class YamlFileLoaderTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG']);

        parent::tearDown();
    }

    public function testSupportsYamlFiles(): void
    {
        $loader = new YamlFileLoader();
        $this->assertTrue($loader->supports($this->getYamlFile()));
        $this->assertFalse($loader->supports($this->getFixturesDir().'/default.xlf'));
        $this->assertFalse($loader->supports($this->getFixturesDir().'/default.php'));
    }

    public function testLoadsNestedAndFlatYamlFilesIntoAString(): void
    {
        $expected = <<<'TXT'
            $GLOBALS['TL_LANG']['MSC']['first'] = 'This is the first source';
            $GLOBALS['TL_LANG']['MSC']['second']['0'] = 'This is the second source';
            $GLOBALS['TL_LANG']['MSC']['third']['with']['1'] = 'This is the third source';
            $GLOBALS['TL_LANG']['MSC']['fifth'] = 'This is the
            fifth source';
            $GLOBALS['TL_LANG']['MSC']['apostrophe'] = 'Here\'s a \\ path with %s and <strong>HTML</strong>';
            $GLOBALS['TL_LANG']['tl_layout']['responsive.css']['1'] = 'This is the fourth source';

            TXT;

        $this->assertSame($expected, new YamlFileLoader()->load($this->getYamlFile()));
    }

    public function testLoadsSequencesLikeNumericMappingKeys(): void
    {
        $loader = new YamlFileLoader();
        $mapping = $loader->load($this->writeYamlFile("MSC:\n  second:\n    0: first\n    1: second\n"));
        $sequence = $loader->load($this->writeYamlFile("MSC:\n  second:\n    - first\n    - second\n"));
        $singleIndex = $loader->load($this->writeYamlFile("MSC:\n  second:\n    1: second\n"));
        $singleFlatIndex = $loader->load($this->writeYamlFile("MSC.second.1: second\n"));
        $sparseMapping = $loader->load($this->writeYamlFile("MSC:\n  second:\n    1: second\n    3: fourth\n"));
        $sparseFlatMapping = $loader->load($this->writeYamlFile("MSC.second.1: second\nMSC.second.3: fourth\n"));

        $this->assertSame($mapping, $sequence);
        $this->assertSame($singleFlatIndex, $singleIndex);
        $this->assertSame($sparseFlatMapping, $sparseMapping);
    }

    public function testIgnoresNullValues(): void
    {
        $actual = new YamlFileLoader()->load($this->writeYamlFile("MSC:\n  first: null\n  second: value\n"));

        $this->assertStringNotContainsString("['first']", $actual);
        $this->assertStringContainsString("['second'] = 'value';", $actual);
    }

    public function testLaterFlatOrNestedKeysOverrideEarlierCollisions(): void
    {
        $loader = new YamlFileLoader();
        $nestedOverridesFlat = $loader->load($this->writeYamlFile("MSC.first: flat\nMSC:\n  first: nested\n"));
        $flatOverridesNested = $loader->load($this->writeYamlFile("MSC:\n  first: nested\nMSC.first: flat\n"));

        $this->assertStringContainsString("['first'] = 'nested';", $nestedOverridesFlat);
        $this->assertStringNotContainsString("['first'] = 'flat';", $nestedOverridesFlat);
        $this->assertStringContainsString("['first'] = 'flat';", $flatOverridesNested);
        $this->assertStringNotContainsString("['first'] = 'nested';", $flatOverridesNested);
    }

    public function testLoadsYamlFilesIntoTheGlobalVariables(): void
    {
        $loader = new YamlFileLoader(true);
        $loader->load($this->getYamlFile());

        $this->assertSame('This is the first source', $GLOBALS['TL_LANG']['MSC']['first']);
        $this->assertSame('This is the second source', $GLOBALS['TL_LANG']['MSC']['second'][0]);
        $this->assertSame('This is the third source', $GLOBALS['TL_LANG']['MSC']['third']['with'][1]);
        $this->assertSame('This is the fourth source', $GLOBALS['TL_LANG']['tl_layout']['responsive.css'][1]);
        $this->assertSame("This is the\nfifth source", $GLOBALS['TL_LANG']['MSC']['fifth']);
        $this->assertSame("Here's a \\ path with %s and <strong>HTML</strong>", $GLOBALS['TL_LANG']['MSC']['apostrophe']);
    }

    public function testOverridesKeysInLanguageArray(): void
    {
        $GLOBALS['TL_LANG']['MSC']['third'] = 'is-a-string';

        $loader = new YamlFileLoader(true);
        $loader->load($this->getYamlFile());

        $this->assertIsArray($GLOBALS['TL_LANG']['MSC']['third']);
        $this->assertSame('This is the third source', $GLOBALS['TL_LANG']['MSC']['third']['with'][1]);
    }

    public function testRejectsInvalidYaml(): void
    {
        $file = $this->writeYamlFile('invalid: [');

        $this->expectException(InvalidResourceException::class);

        new YamlFileLoader()->load($file);
    }

    public function testRejectsDuplicateKeys(): void
    {
        $file = $this->writeYamlFile("MSC.first: first\nMSC.first: second\n");

        $this->expectException(InvalidResourceException::class);

        new YamlFileLoader()->load($file);
    }

    private function getYamlFile(): string
    {
        return $this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao/languages/en/default.yaml';
    }

    private function writeYamlFile(string $contents): string
    {
        $path = Path::join($this->getTempDir(), 'language.yaml');

        $filesystem = new Filesystem();
        $filesystem->dumpFile($path, $contents);

        return $path;
    }
}
