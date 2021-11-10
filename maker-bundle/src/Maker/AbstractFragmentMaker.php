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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\MakerBundle\Filesystem\ContaoDirectoryLocator;
use Contao\MakerBundle\Generator\ClassGenerator;
use Contao\MakerBundle\Generator\DcaGenerator;
use Contao\MakerBundle\Generator\LanguageFileGenerator;
use Contao\MakerBundle\Generator\TemplateGenerator;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DependencyInjection\Container;
use Webmozart\PathUtil\Path;

abstract class AbstractFragmentMaker extends AbstractMaker
{
    protected ContaoFramework $framework;
    protected TemplateGenerator $templateGenerator;
    protected ClassGenerator $classGenerator;
    protected DcaGenerator $dcaGenerator;
    protected LanguageFileGenerator $languageFileGenerator;
    protected ContaoDirectoryLocator $contaoDirectoryLocator;

    public function __construct(ContaoFramework $framework, TemplateGenerator $templateGenerator, ClassGenerator $classGenerator, DcaGenerator $dcaGenerator, LanguageFileGenerator $languageFileGenerator, ContaoDirectoryLocator $contaoDirectoryLocator)
    {
        $this->framework = $framework;
        $this->templateGenerator = $templateGenerator;
        $this->classGenerator = $classGenerator;
        $this->dcaGenerator = $dcaGenerator;
        $this->languageFileGenerator = $languageFileGenerator;
        $this->contaoDirectoryLocator = $contaoDirectoryLocator;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $requiredValidator = static function ($input) {
            if (null === $input || '' === $input) {
                throw new RuntimeCommandException('This value cannot be blank');
            }

            return $input;
        };

        $definition = $command->getDefinition();

        // Ask whether an empty DCA palette should be created
        $command->addArgument('addEmptyDcaPalette', InputArgument::OPTIONAL);
        $question = new ConfirmationQuestion('Do you want to add an empty DCA palette?', true);
        $input->setArgument('addEmptyDcaPalette', $io->askQuestion($question));

        $command->addArgument('category', InputArgument::OPTIONAL, 'Choose a category');
        $argument = $definition->getArgument('category');
        $categories = $this->getExistingCategories();

        $io->writeln(' <fg=green>Suggested categories:</>');
        $io->listing($categories);

        $question = new Question($argument->getDescription());
        $question->setValidator($requiredValidator);
        $question->setAutocompleterValues($categories);

        $input->setArgument('category', $io->askQuestion($question));

        // Ask whether language files should be generated
        $command->addArgument('addTranslation', InputArgument::OPTIONAL);
        $question = new ConfirmationQuestion('Do you want to add translations?', true);
        $input->setArgument('addTranslation', $io->askQuestion($question));

        if ($input->getArgument('addTranslation')) {
            $command
                ->addArgument('sourceName', InputArgument::OPTIONAL, 'Enter the English name')
                ->addArgument('sourceDescription', InputArgument::OPTIONAL, 'Enter the English description')
            ;

            foreach (['sourceName', 'sourceDescription'] as $field) {
                $argument = $definition->getArgument($field);

                $question = new Question($argument->getDescription());
                $question->setValidator($requiredValidator);

                $input->setArgument($field, $io->askQuestion($question));
            }

            $i = 0;

            while (true) {
                $command->addArgument('addTranslation_'.$i, InputArgument::OPTIONAL);
                $question = new ConfirmationQuestion('Do you want to add another translation?', false);
                $input->setArgument('addTranslation_'.$i, $io->askQuestion($question));

                if (!$input->getArgument('addTranslation_'.$i)) {
                    break;
                }

                $command
                    ->addArgument('language_'.$i, InputArgument::OPTIONAL, 'Which language do you want to add? (e.g. <fg=yellow>de</>)')
                    ->addArgument('translatedName_'.$i, InputArgument::OPTIONAL, 'Enter the translated name')
                    ->addArgument('translatedDescription_'.$i, InputArgument::OPTIONAL, 'Enter the translated description')
                ;

                $argument = $definition->getArgument('language_'.$i);
                $question = new Question($argument->getDescription());
                $input->setArgument('language_'.$i, $io->askQuestion($question));

                foreach (['translatedName_'.$i, 'translatedDescription_'.$i] as $field) {
                    $argument = $definition->getArgument($field);

                    $question = new Question($argument->getDescription());
                    $question->setValidator($requiredValidator);

                    $input->setArgument($field, $io->askQuestion($question));
                }

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
            $this->contaoDirectoryLocator->getConfigDirectory(),
            'templates',
            sprintf('%s_%s.html5', $this->getTemplatePrefix(), Container::underscore($className))
        );
    }

    /**
     * @return array<int, string>
     */
    protected function getExistingCategories(): array
    {
        $this->framework->initialize();

        return array_keys((array) $GLOBALS[$this->getGlobalsRegistryKey()]);
    }

    protected function getClassNameWithoutSuffix(string $className): string
    {
        if ('Controller' === substr($className, -10)) {
            $className = substr($className, 0, -10);
        }

        return $className;
    }
}
