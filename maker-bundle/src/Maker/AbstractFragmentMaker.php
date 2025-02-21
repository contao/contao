<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Maker;

use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\MakerBundle\Generator\ClassGenerator;
use Contao\MakerBundle\Generator\DcaGenerator;
use Contao\MakerBundle\Generator\LanguageFileGenerator;
use Contao\MakerBundle\Generator\TemplateGenerator;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Path;

abstract class AbstractFragmentMaker extends AbstractMaker
{
    public function __construct(
        protected readonly ContaoFramework $framework,
        protected readonly TemplateGenerator $templateGenerator,
        protected readonly ClassGenerator $classGenerator,
        protected readonly DcaGenerator $dcaGenerator,
        protected readonly LanguageFileGenerator $languageFileGenerator,
        protected readonly FileManager $fileManager,
        protected readonly string $projectDir,
    ) {
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $this->askForCategory($input, $io, $command);
        $this->askForDcaPalette($input, $io, $command);
        $this->askForTranslation($input, $io, $command);

        if ($input->getArgument('add-translation')) {
            $this->askForSourceName($input, $io, $command);
            $this->askForSourceDescription($input, $io, $command);

            $i = 0;

            while (true) {
                $this->askForAdditionalTranslation($input, $io, $command, $i);

                if (!$input->getArgument('add-translation-'.$i)) {
                    break;
                }

                $this->askForLanguage($input, $io, $command, $i);
                $this->askForTargetName($input, $io, $command, $i);
                $this->askForTargetDescription($input, $io, $command, $i);

                ++$i;
            }
        }
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    abstract protected function getGlobalsRegistryKey(): string;

    abstract protected function getTemplatePrefix(): string;

    protected function getTemplateName(string $className): string
    {
        return Path::join(
            $this->projectDir,
            'contao/templates',
            $this->getTemplatePrefix(),
            \sprintf('%s.html.twig', Container::underscore($className)),
        );
    }

    /**
     * @return array<int, string>
     */
    protected function getCategories(): array
    {
        $this->framework->initialize();

        return array_keys((array) $GLOBALS[$this->getGlobalsRegistryKey()]);
    }

    protected function getClassNameWithoutSuffix(string $className): string
    {
        if (str_ends_with($className, 'Controller')) {
            $className = substr($className, 0, -10);
        }

        return $className;
    }

    private function askForCategory(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command->addArgument('category', InputArgument::REQUIRED);

        $categories = $this->getCategories();

        $io->writeln(' <fg=green>Suggested categories:</>');
        $io->listing($categories);

        $attributeClass = match (static::class) {
            MakeContentElement::class => AsContentElement::class,
            MakeFrontendModule::class => AsFrontendModule::class,
            default => null,
        };

        $default = null;

        if ($attributeClass) {
            $reflection = new \ReflectionClass($attributeClass);
            $params = $reflection->getConstructor()->getParameters();

            foreach ($params as $param) {
                if ('category' === $param->getName()) {
                    $default = $param->getDefaultValue();

                    break;
                }
            }
        }

        $question = new Question('Choose a category', $default);
        $question->setAutocompleterValues($categories);

        $category = (string) $io->askQuestion($question);

        $input->setArgument('category', $category === (string) $default ? null : $category);
    }

    private function askForDcaPalette(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command->addArgument('add-palette', InputArgument::REQUIRED);

        $question = new ConfirmationQuestion('Do you want to add a palette?');

        $input->setArgument('add-palette', $io->askQuestion($question));
    }

    private function askForTranslation(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command->addArgument('add-translation', InputArgument::REQUIRED);

        $question = new ConfirmationQuestion('Do you want to add a translation?');

        $input->setArgument('add-translation', $io->askQuestion($question));
    }

    private function askForSourceName(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command->addArgument('source-name', InputArgument::OPTIONAL);

        $question = new Question('Enter the English name');
        $question->setValidator(Validator::notBlank(...));

        $input->setArgument('source-name', $io->askQuestion($question));
    }

    private function askForSourceDescription(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $command->addArgument('source-description', InputArgument::OPTIONAL);

        $question = new Question('Enter the English description');
        $question->setValidator(Validator::notBlank(...));

        $input->setArgument('source-description', $io->askQuestion($question));
    }

    private function askForAdditionalTranslation(InputInterface $input, ConsoleStyle $io, Command $command, int $count): void
    {
        $command->addArgument('add-translation-'.$count, InputArgument::OPTIONAL);

        $question = new ConfirmationQuestion('Do you want to add another translation?', false);

        $input->setArgument('add-translation-'.$count, $io->askQuestion($question));
    }

    private function askForLanguage(InputInterface $input, ConsoleStyle $io, Command $command, int $count): void
    {
        $command->addArgument('language-'.$count, InputArgument::OPTIONAL);

        $question = new Question('Which language do you want to add? (e.g. <fg=yellow>de</>)');
        $question->setValidator(Validator::notBlank(...));

        $input->setArgument('language-'.$count, $io->askQuestion($question));
    }

    private function askForTargetName(InputInterface $input, ConsoleStyle $io, Command $command, int $count): void
    {
        $command->addArgument('target-name-'.$count, InputArgument::OPTIONAL);

        $question = new Question('Enter the translated name');
        $question->setValidator(Validator::notBlank(...));

        $input->setArgument('target-name-'.$count, $io->askQuestion($question));
    }

    private function askForTargetDescription(InputInterface $input, ConsoleStyle $io, Command $command, int $count): void
    {
        $command->addArgument('target-description-'.$count, InputArgument::OPTIONAL);

        $question = new Question('Enter the translated description');
        $question->setValidator(Validator::notBlank(...));

        $input->setArgument('target-description-'.$count, $io->askQuestion($question));
    }
}
