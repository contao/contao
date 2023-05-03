<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Command;

use Contao\ManagerBundle\Command\GenerateAppSecretCommand;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class GenerateAppSecretCommandTest extends ContaoTestCase
{
    private const DEFAULT_SECRET = 'ThisTokenIsNotSoSecretChangeIt';

    private static Filesystem $filesystem;
    private static string $tempDir;
    private static string $envPath;
    private static string $envLocalPath;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$filesystem = new Filesystem();
        self::$tempDir = self::getTempDir();
        self::$envPath = Path::join(self::$tempDir, '.env');
        self::$envLocalPath = Path::join(self::$tempDir, '.env.local');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        self::$filesystem->remove(self::$envPath);
        self::$filesystem->remove(self::$envLocalPath);
        unset($_SERVER['APP_SECRET']);
    }

    public function testNameAndArguments(): void
    {
        $command = $this->getCommand();

        $this->assertSame('contao:generate-app-secret', $command->getName());
        $this->assertTrue($command->getDefinition()->hasOption('force'));
        $this->assertTrue($command->getDefinition()->hasOption('length'));
    }

    public function testGeneratesEnvFiles(): void
    {
        $this->assertFileNotExists(self::$envPath);
        $this->assertFileNotExists(self::$envLocalPath);

        $tester = $this->getCommandTester();
        $tester->execute([]);

        $this->assertFileExists(self::$envPath);
        $this->assertFileExists(self::$envLocalPath);
    }

    public function testGeneratesAppSecret(): void
    {
        $this->assertFileNotExists(self::$envLocalPath);

        $tester = $this->getCommandTester();
        $tester->execute([]);

        $this->assertFileExists(self::$envLocalPath);

        $envVars = (new Dotenv())->parse(file_get_contents(self::$envLocalPath), '.env.local');

        $this->assertArrayHasKey('APP_SECRET', $envVars);
        $this->assertTrue(64 === \strlen($envVars['APP_SECRET']));
    }

    public function testDoesNotGenerateAppSecretIfDefined(): void
    {
        $this->assertFileNotExists(self::$envLocalPath);

        $tester = $this->getCommandTester('foobar');
        $tester->execute([]);

        $this->assertFileNotExists(self::$envLocalPath);
        $this->assertStringContainsString('Secret is already set.', $tester->getDisplay(true));
    }

    public function testForcesTheGenerationOfTheAppSecret(): void
    {
        $_SERVER['APP_SECRET'] = 'foobar';
        $this->assertFileNotExists(self::$envLocalPath);

        $tester = $this->getCommandTester();
        $tester->execute(['--force' => true]);

        $this->assertFileExists(self::$envLocalPath);

        $envVars = (new Dotenv())->parse(file_get_contents(self::$envLocalPath), '.env.local');

        $this->assertArrayHasKey('APP_SECRET', $envVars);
        $this->assertTrue(64 === \strlen($envVars['APP_SECRET']));
    }

    public function testSetsLengthOfAppSecret(): void
    {
        $this->assertFileNotExists(self::$envLocalPath);

        $tester = $this->getCommandTester();
        $tester->execute(['--length' => 64]);

        $this->assertFileExists(self::$envLocalPath);

        $envVars = (new Dotenv())->parse(file_get_contents(self::$envLocalPath), '.env.local');

        $this->assertArrayHasKey('APP_SECRET', $envVars);
        $this->assertTrue(64 === \strlen($envVars['APP_SECRET']));
    }

    public function testThrowsExceptionIfInvalidLengthGiven(): void
    {
        $tester = $this->getCommandTester();

        $this->expectException(InvalidOptionException::class);

        $tester->execute(['--length' => 0]);
    }

    private function getCommand(string $secret = self::DEFAULT_SECRET): GenerateAppSecretCommand
    {
        return new GenerateAppSecretCommand(self::$tempDir, $secret);
    }

    private function getCommandTester(string $secret = self::DEFAULT_SECRET): CommandTester
    {
        return new CommandTester($this->getCommand($secret));
    }
}
