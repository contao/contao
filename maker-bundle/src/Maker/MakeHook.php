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

use Contao\ArticleModel;
use Contao\Comments;
use Contao\ContentElement;
use Contao\ContentModel;
use Contao\Database\Result;
use Contao\DataContainer;
use Contao\Email;
use Contao\File;
use Contao\Form;
use Contao\FormModel;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\Image;
use Contao\LayoutModel;
use Contao\MakerBundle\Code\ImportExtractor;
use Contao\MakerBundle\Code\SignatureGenerator;
use Contao\MakerBundle\Generator\ClassGenerator;
use Contao\MakerBundle\Model\MethodDefinition;
use Contao\MemberModel;
use Contao\Model;
use Contao\Module;
use Contao\ModuleArticle;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\Template;
use Contao\User;
use Contao\Widget;
use Contao\ZipReader;
use Contao\ZipWriter;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;

class MakeHook extends AbstractMaker
{
    private ClassGenerator $classGenerator;
    private SignatureGenerator $signatureGenerator;
    private ImportExtractor $importExtractor;

    public function __construct(ClassGenerator $classGenerator, SignatureGenerator $signatureGenerator, ImportExtractor $importExtractor)
    {
        $this->classGenerator = $classGenerator;
        $this->signatureGenerator = $signatureGenerator;
        $this->importExtractor = $importExtractor;
    }

    public static function getCommandName(): string
    {
        return 'make:contao:hook';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Creates a hook')
            ->addArgument('className', InputArgument::OPTIONAL, 'Enter a class name for the hook')
        ;
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        $definition = $command->getDefinition();

        $command->addArgument('hook', InputArgument::OPTIONAL, 'Choose a hook to implement');
        $argument = $definition->getArgument('hook');
        $hooks = $this->getAvailableHooks();

        $io->writeln(' <fg=green>Suggested Hooks:</>');
        $io->listing(array_keys($hooks));

        $question = new Question($argument->getDescription());
        $question->setAutocompleterValues(array_keys($hooks));

        $input->setArgument('hook', $io->askQuestion($question));
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $availableHooks = $this->getAvailableHooks();
        $hook = $input->getArgument('hook');
        $name = $input->getArgument('className');

        if (!\array_key_exists($hook, $availableHooks)) {
            $io->error(sprintf('Hook definition "%s" not found.', $hook));

            return;
        }

        /** @var MethodDefinition $definition */
        $definition = $availableHooks[$hook];

        $signature = $this->signatureGenerator->generate($definition, '__invoke');
        $uses = $this->importExtractor->extract($definition);
        $elementDetails = $generator->createClassNameDetails($name, 'EventListener\\');

        $this->classGenerator->generate([
            'source' => 'hook/Hook.tpl.php',
            'fqcn' => $elementDetails->getFullName(),
            'variables' => [
                'className' => $elementDetails->getShortName(),
                'hook' => $hook,
                'signature' => $signature,
                'uses' => $uses,
                'body' => $definition->getBody(),
            ],
        ]);

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    /**
     * @return array<string, MethodDefinition>
     */
    private function getAvailableHooks(): array
    {
        return [
            'activateAccount' => new MethodDefinition(
                'void',
                [
                    'member' => MemberModel::class,
                    'module' => Module::class,
                ]
            ),
            'activateRecipient' => new MethodDefinition(
                'void',
                [
                    'mail' => 'string',
                    'recipientIds' => 'array',
                    'channelIds' => 'array',
                ]
            ),
            'addComment' => new MethodDefinition(
                'void',
                [
                    'commentId' => 'int',
                    'commentData' => 'array',
                    'comments' => Comments::class,
                ]
            ),
            'addCustomRegexp' => new MethodDefinition(
                'bool',
                [
                    'regexp' => 'string',
                    'input' => '',
                    'widget' => Widget::class,
                ]
            ),
            'addLogEntry' => new MethodDefinition(
                'void',
                [
                    'message' => 'string',
                    'func' => 'string',
                    'action' => 'string',
                ]
            ),
            'checkCredentials' => new MethodDefinition(
                'bool',
                [
                    'username' => 'string',
                    'credentials' => 'string',
                    'user' => User::class,
                ]
            ),
            'closeAccount' => new MethodDefinition(
                'void',
                [
                    'userId' => 'int',
                    'mode' => 'string',
                    'module' => Module::class,
                ]
            ),
            'colorizeLogEntries' => new MethodDefinition(
                'string',
                [
                    'row' => 'array',
                    'label' => 'string',
                ]
            ),
            'compareThemeFiles' => new MethodDefinition(
                'string',
                [
                    'xml' => '\DomDocument',
                    'zip' => ZipReader::class,
                ]
            ),
            'compileArticle' => new MethodDefinition(
                'void',
                [
                    'template' => FrontendTemplate::class,
                    'data' => 'array',
                    'module' => Module::class,
                ]
            ),
            'compileDefinition' => new MethodDefinition(
                'string',
                [
                    'row' => 'array',
                    'writeToFile' => 'bool',
                    'vars' => 'array',
                    'parent' => 'array',
                ]
            ),
            'compileFormFields' => new MethodDefinition(
                'array',
                [
                    'fields' => 'array',
                    'formId' => 'string',
                    'form' => Form::class,
                ]
            ),
            'createDefinition' => new MethodDefinition(
                'array',
                [
                    'key' => 'string',
                    'value' => 'string',
                    'definition' => 'string',
                    '&dataSet' => 'array',
                ]
            ),
            'createNewUser' => new MethodDefinition(
                'void',
                [
                    'userId' => 'int',
                    'userData' => 'array',
                    'module' => Module::class,
                ]
            ),
            'customizeSearch' => new MethodDefinition(
                'void',
                [
                    '&pageIds' => 'array',
                    'keywords' => 'string',
                    'queryType' => 'string',
                    'fuzzy' => 'bool',
                    'module' => Module::class,
                ]
            ),
            'executePostActions' => new MethodDefinition(
                'void',
                [
                    'action' => 'string',
                    'dc' => DataContainer::class,
                ]
            ),
            'executePreActions' => new MethodDefinition(
                'void',
                [
                    'action' => 'string',
                ]
            ),
            'executeResize' => new MethodDefinition(
                '?string',
                [
                    'image' => Image::class,
                ]
            ),
            'exportTheme' => new MethodDefinition(
                'void',
                [
                    'xml' => '\DomDocument',
                    'zipArchive' => ZipWriter::class,
                    'themeId' => 'int',
                ]
            ),
            'extractThemeFiles' => new MethodDefinition(
                'void',
                [
                    'xml' => '\DomDocument',
                    'zipArchive' => ZipReader::class,
                    'themeId' => 'int',
                    'mapper' => 'array',
                ]
            ),
            'generateBreadcrumb' => new MethodDefinition(
                'array',
                [
                    'items' => 'array',
                    'module' => Module::class,
                ]
            ),
            'generateFrontendUrl' => new MethodDefinition(
                'string',
                [
                    'page' => 'array',
                    'params' => 'string',
                    'url' => 'string',
                ]
            ),
            'generatePage' => new MethodDefinition(
                'void',
                [
                    'pageModel' => PageModel::class,
                    'layout' => LayoutModel::class,
                    'pageRegular' => PageRegular::class,
                ]
            ),
            'generateXmlFiles' => new MethodDefinition(
                'void',
                []
            ),
            'getAllEvents' => new MethodDefinition(
                'array',
                [
                    'events' => 'array',
                    'calendars' => 'array',
                    'timeStart' => 'int',
                    'timeEnd' => 'int',
                    'module' => Module::class,
                ]
            ),
            'getArticle' => new MethodDefinition(
                'void',
                [
                    'article' => ArticleModel::class,
                ]
            ),
            'getArticles' => new MethodDefinition(
                '?string',
                [
                    'pageId' => 'int',
                    'column' => 'string',
                ]
            ),
            'getAttributesFromDca' => new MethodDefinition(
                'array',
                [
                    'attributes' => 'array',
                    'dc' => [DataContainer::class, 'null'],
                ]
            ),
            'getCombinedFile' => new MethodDefinition(
                'string',
                [
                    'content' => 'string',
                    'key' => 'string',
                    'mode' => 'string',
                    'file' => 'array',
                ]
            ),
            'getContentElement' => new MethodDefinition(
                'string',
                [
                    'contentModel' => ContentModel::class,
                    'buffer' => 'string',
                    'contentElement' => ContentElement::class,
                ]
            ),
            'getCountries' => new MethodDefinition(
                'void',
                [
                    '&translatedCountries' => 'array',
                    'allCountries' => 'array',
                ]
            ),
            'getForm' => new MethodDefinition(
                'string',
                [
                    'form' => FormModel::class,
                    'buffer' => 'string',
                ]
            ),
            'getFrontendModule' => new MethodDefinition(
                'string',
                [
                    'moduleModel' => ModuleModel::class,
                    'buffer' => 'string',
                    'module' => Module::class,
                ]
            ),
            'getImage' => new MethodDefinition(
                '?string',
                [
                    'originalPath' => 'string',
                    'width' => 'int',
                    'height' => 'int',
                    'mode' => 'string',
                    'cacheName' => 'string',
                    'file' => File::class,
                    'targetPath' => 'string',
                    'imageObject' => Image::class,
                ]
            ),
            'getLanguages' => new MethodDefinition(
                'void',
                [
                    '&compiledLanguages' => 'array',
                    'languages' => 'array',
                    'langsNative' => 'array',
                    'installedOnly' => 'bool',
                ]
            ),
            'getPageIdFromUrl' => new MethodDefinition(
                'array',
                [
                    'fragments' => 'array',
                ]
            ),
            'getPageLayout' => new MethodDefinition(
                'void',
                [
                    'pageModel' => PageModel::class,
                    'layout' => LayoutModel::class,
                    'pageRegular' => PageRegular::class,
                ]
            ),
            'getPageStatusIcon' => new MethodDefinition(
                'string',
                [
                    'page' => 'object',
                    'image' => 'string',
                ]
            ),
            'getRootPageFromUrl' => new MethodDefinition(
                PageModel::class,
                []
            ),
            'getSearchablePages' => new MethodDefinition(
                'array',
                [
                    'pages' => 'array',
                    'rootId' => ['int', 'null'],
                    'isSitemap' => ['bool', 'false'],
                    'language' => ['string', 'null'],
                ]
            ),
            'getSystemMessages' => new MethodDefinition(
                'string',
                []
            ),
            'getUserNavigation' => new MethodDefinition(
                'array',
                [
                    'modules' => 'array',
                    'showAll' => 'bool',
                ]
            ),
            'importUser' => new MethodDefinition(
                'bool',
                [
                    'username' => 'string',
                    'password' => 'string',
                    'table' => 'string',
                ]
            ),
            'indexPage' => new MethodDefinition(
                'void',
                [
                    'content' => 'string',
                    'pageData' => 'array',
                    '&indexData' => 'array',
                ]
            ),
            'initializeSystem' => new MethodDefinition(
                'void',
                []
            ),
            'insertTagFlags' => new MethodDefinition(
                null,
                [
                    'flag' => 'string',
                    'tag' => 'string',
                    'cachedValue' => 'string',
                    'flags' => 'array',
                    'useCache' => 'bool',
                    'tags' => 'array',
                    'cache' => 'array',
                    '_rit' => 'int',
                    '_cnt' => 'int',
                ]
            ),
            'isAllowedToEditComment' => new MethodDefinition(
                'bool',
                [
                    'parentId' => 'int',
                    'parentTable' => 'string',
                ]
            ),
            'isVisibleElement' => new MethodDefinition(
                'bool',
                [
                    'element' => Model::class,
                    'isVisible' => 'bool',
                ]
            ),
            'listComments' => new MethodDefinition(
                'string',
                [
                    'comments' => 'array',
                ]
            ),
            'loadDataContainer' => new MethodDefinition(
                'void',
                [
                    'table' => 'string',
                ]
            ),
            'loadFormField' => new MethodDefinition(Widget::class,
                [
                    'widget' => Widget::class,
                    'formId' => 'string',
                    'formData' => 'array',
                    'form' => Form::class,
                ]
            ),
            'loadLanguageFile' => new MethodDefinition(
                'void',
                [
                    'name' => 'string',
                    'currentLanguage' => 'string',
                    'cacheKey' => 'string',
                ]
            ),
            'loadPageDetails' => new MethodDefinition(
                'void',
                [
                    'parentModels' => 'array',
                    'page' => PageModel::class,
                ]
            ),
            'modifyFrontendPage' => new MethodDefinition(
                'string',
                [
                    'buffer' => 'string',
                    'templateName' => 'string',
                ]
            ),
            'newsListCountItems' => new MethodDefinition(
                null,
                [
                    'newsArchives' => 'array',
                    'featuredOnly' => 'bool',
                    'module' => Module::class,
                ]
            ),
            'newsListFetchItems' => new MethodDefinition(
                null,
                [
                    'newsArchives' => 'array',
                    'featuredOnly' => '?bool',
                    'limit' => 'int',
                    'offset' => 'int',
                    'module' => Module::class,
                ]
            ),
            'outputBackendTemplate' => new MethodDefinition(
                'string',
                [
                    'buffer' => 'string',
                    'template' => 'string',
                ]
            ),
            'outputFrontendTemplate' => new MethodDefinition(
                'string',
                [
                    'buffer' => 'string',
                    'template' => 'string',
                ]
            ),
            'parseArticles' => new MethodDefinition(
                'void',
                [
                    'template' => FrontendTemplate::class,
                    'newsEntry' => 'array',
                    'module' => Module::class,
                ]
            ),
            'parseDate' => new MethodDefinition(
                'string',
                [
                    'formattedDate' => 'string',
                    'format' => 'string',
                    'timestamp' => '?int',
                ]
            ),
            'parseFrontendTemplate' => new MethodDefinition(
                'string',
                [
                    'buffer' => 'string',
                    'templateName' => 'string',
                    'template' => FrontendTemplate::class,
                ]
            ),
            'parseTemplate' => new MethodDefinition(
                'void',
                [
                    'template' => Template::class,
                ]
            ),
            'parseWidget' => new MethodDefinition(
                'string',
                [
                    'buffer' => 'string',
                    'widget' => Widget::class,
                ]
            ),
            'postAuthenticate' => new MethodDefinition(
                'void',
                [
                    'user' => User::class,
                ]
            ),
            'postDownload' => new MethodDefinition(
                'void',
                [
                    'file' => 'string',
                ]
            ),
            'postLogin' => new MethodDefinition(
                'void',
                [
                    'user' => User::class,
                ]
            ),
            'postLogout' => new MethodDefinition(
                'void',
                [
                    'user' => User::class,
                ]
            ),
            'postUpload' => new MethodDefinition(
                'void',
                [
                    'files' => 'array',
                ]
            ),
            'prepareFormData' => new MethodDefinition(
                'void',
                [
                    '&submittedData' => 'array',
                    'labels' => 'array',
                    'fields' => 'array',
                    'form' => Form::class,
                ]
            ),
            'printArticleAsPdf' => new MethodDefinition(
                'void',
                [
                    'articleContent' => 'string',
                    'module' => ModuleArticle::class,
                ]
            ),
            'processFormData' => new MethodDefinition(
                'void',
                [
                    'submittedData' => 'array',
                    'formData' => 'array',
                    'files' => '?array',
                    'labels' => 'array',
                    'form' => Form::class,
                ]
            ),
            'removeOldFeeds' => new MethodDefinition(
                'array',
                []
            ),
            'removeRecipient' => new MethodDefinition(
                'void',
                [
                    'email' => 'string',
                    'channels' => 'array',
                ]
            ),
            'replaceDynamicScriptTags' => new MethodDefinition(
                'string',
                [
                    'buffer' => 'string',
                ]
            ),
            'replaceInsertTags' => new MethodDefinition(
                null,
                [
                    'insertTag' => 'string',
                    'useCache' => 'bool',
                    'cachedValue' => 'string',
                    'flags' => 'array',
                    'tags' => 'array',
                    'cache' => 'array',
                    '_rit' => 'int',
                    '_cnt' => 'int',
                ]
            ),
            'reviseTable' => new MethodDefinition(
                'bool',
                [
                    'table' => 'string',
                    'newRecords' => '?array',
                    'parentTable' => '?string',
                    'childTables' => '?array',
                ]
            ),
            'sendNewsletter' => new MethodDefinition(
                'void',
                [
                    'email' => Email::class,
                    'newsletter' => Result::class,
                    'recipient' => 'array',
                    'text' => 'string',
                    'html' => 'string',
                ]
            ),
            'setCookie' => new MethodDefinition(
                'object',
                [
                    'cookie' => 'object',
                ]
            ),
            'setNewPassword' => new MethodDefinition(
                'void',
                [
                    'member' => null,
                    'password' => 'string',
                    'module' => [Module::class, 'null'],
                ]
            ),
            'sqlCompileCommands' => new MethodDefinition(
                'array',
                [
                    'sql' => 'array',
                ]
            ),
            'sqlGetFromDB' => new MethodDefinition(
                'array',
                [
                    'sql' => 'array',
                ]
            ),
            'sqlGetFromDca' => new MethodDefinition(
                'array',
                [
                    'sql' => 'array',
                ]
            ),
            'sqlGetFromFile' => new MethodDefinition(
                'array',
                [
                    'sql' => 'array',
                ]
            ),
            'storeFormData' => new MethodDefinition(
                'array',
                [
                    'data' => 'array',
                    'form' => Form::class,
                ]
            ),
            'updatePersonalData' => new MethodDefinition(
                'void',
                [
                    'member' => FrontendUser::class,
                    'data' => 'array',
                    'module' => Module::class,
                ]
            ),
            'validateFormField' => new MethodDefinition(
                Widget::class,
                [
                    'widget' => Widget::class,
                    'formId' => 'string',
                    'formData' => 'array',
                    'form' => Form::class,
                ]
            ),
        ];
    }
}
