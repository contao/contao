<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag\Resolver;

use Contao\ArrayUtil;
use Contao\ArticleModel;
use Contao\Controller;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Util\UrlUtil;
use Contao\Database;
use Contao\Date;
use Contao\Environment;
use Contao\File;
use Contao\FilesModel;
use Contao\Frontend;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\Idna;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Routing\Exception\ExceptionInterface;

#[AsInsertTag('lang')]
#[AsInsertTag('br')]
#[AsInsertTag('email')]
#[AsInsertTag('email_open')]
#[AsInsertTag('email_url')]
#[AsInsertTag('label')]
#[AsInsertTag('user')]
#[AsInsertTag('email_close')]
#[AsInsertTag('insert_article')]
#[AsInsertTag('insert_content')]
#[AsInsertTag('insert_module')]
#[AsInsertTag('insert_form')]
#[AsInsertTag('article')]
#[AsInsertTag('article_open')]
#[AsInsertTag('article_url')]
#[AsInsertTag('article_title')]
#[AsInsertTag('article_teaser')]
#[AsInsertTag('last_update')]
#[AsInsertTag('version')]
#[AsInsertTag('env')]
#[AsInsertTag('page')]
#[AsInsertTag('abbr')]
#[AsInsertTag('acronym')]
#[AsInsertTag('figure')]
#[AsInsertTag('image')]
#[AsInsertTag('picture')]
#[AsInsertTag('file')]
/**
 * @internal
 *
 * @todo Refactor into separate insert tags
 */
class LegacyInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $result = '';
        $outputType = OutputType::html;

        // Replace the tag
        switch ($insertTag->getName()) {
            // Accessibility tags
            case 'lang':
                if (empty($insertTag->getParameters()->get(0))) {
                    $result = '</span>';
                } else {
                    $result = '<span lang="'.StringUtil::specialchars($insertTag->getParameters()->get(0)).'">';
                }
                break;

            // Line break
            case 'br':
                $result = '<br>';
                break;

            // E-mail addresses
            case 'email':
            case 'email_open':
            case 'email_url':
                if (empty($insertTag->getParameters()->get(0))) {
                    break;
                }

                $strEmail = StringUtil::specialcharsUrl(StringUtil::encodeEmail($insertTag->getParameters()->get(0)));

                // Replace the tag
                switch ($insertTag->getName()) {
                    case 'email':
                        $result = '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;'.$strEmail.'" class="email">'.preg_replace('/\?.*$/', '', $strEmail).'</a>';
                        break;

                    case 'email_open':
                        $result = '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;'.$strEmail.'" title="'.$strEmail.'" class="email">';
                        break;

                    case 'email_url':
                        $result = $strEmail;
                        break;
                }
                break;

            // Label tags
            case 'label':
                $outputType = OutputType::text;
                $keys = explode(':', $insertTag->getParameters()->get(0));

                if (\count($keys) < 2) {
                    break;
                }

                if ('LNG' === $keys[0] && 2 === \count($keys)) {
                    try {
                        $result = $this->container->get('contao.intl.locales')->getDisplayNames([$keys[1]])[$keys[1]];
                        break;
                    } catch (\Throwable) {
                        // Fall back to loading the label via $GLOBALS['TL_LANG']
                    }
                }

                if ('CNT' === $keys[0] && 2 === \count($keys)) {
                    try {
                        $result = $this->container->get('contao.intl.countries')->getCountries()[strtoupper($keys[1])] ?? '';
                        break;
                    } catch (\Throwable) {
                        // Fall back to loading the label via $GLOBALS['TL_LANG']
                    }
                }

                $file = $keys[0];

                // Map the key (see #7217)
                switch ($file) {
                    case 'CNT':
                        $file = 'countries';
                        break;

                    case 'LNG':
                        $file = 'languages';
                        break;

                    case 'MOD':
                    case 'FMD':
                        $file = 'modules';
                        break;

                    case 'FFL':
                        $file = 'tl_form_field';
                        break;

                    case 'CACHE':
                        $file = 'tl_page';
                        break;

                    case 'XPL':
                        $file = 'explain';
                        break;

                    case 'XPT':
                        $file = 'exception';
                        break;

                    case 'MSC':
                    case 'ERR':
                    case 'CTE':
                    case 'PTY':
                    case 'FOP':
                    case 'CHMOD':
                    case 'DAYS':
                    case 'MONTHS':
                    case 'UNITS':
                    case 'CONFIRM':
                    case 'DP':
                    case 'COLS':
                    case 'SECTIONS':
                    case 'DCA':
                    case 'CRAWL':
                        $file = 'default';
                        break;
                }

                try {
                    System::loadLanguageFile($file);
                } catch (\InvalidArgumentException $exception) {
                    $this->container->get('monolog.logger.contao.error')->error('Invalid label insert tag '.$insertTag->serialize().' on page '.Environment::get('uri').': '.$exception->getMessage());
                }

                if (2 === \count($keys)) {
                    $result = $GLOBALS['TL_LANG'][$keys[0]][$keys[1]] ?? '';
                } else {
                    $result = $GLOBALS['TL_LANG'][$keys[0]][$keys[1]][$keys[2]] ?? '';
                }
                break;

            // Front end user
            case 'user':
                if ($this->container->get('contao.security.token_checker')->hasFrontendUser()) {
                    $outputType = OutputType::text;
                    $value = FrontendUser::getInstance()->{$insertTag->getParameters()->get(0)};

                    if (!$value) {
                        $result = $value;
                        break;
                    }

                    Controller::loadDataContainer('tl_member');

                    if ('password' === ($GLOBALS['TL_DCA']['tl_member']['fields'][$insertTag->getParameters()->get(0)]['inputType'] ?? null)) {
                        break;
                    }

                    $value = StringUtil::deserialize($value);

                    $rgxp = $GLOBALS['TL_DCA']['tl_member']['fields'][$insertTag->getParameters()->get(0)]['eval']['rgxp'] ?? null;
                    $opts = $GLOBALS['TL_DCA']['tl_member']['fields'][$insertTag->getParameters()->get(0)]['options'] ?? null;
                    $rfrc = $GLOBALS['TL_DCA']['tl_member']['fields'][$insertTag->getParameters()->get(0)]['reference'] ?? null;

                    if ('date' === $rgxp) {
                        $result = Date::parse($GLOBALS['objPage']->dateFormat ?? $GLOBALS['TL_CONFIG']['dateFormat'] ?? '', $value);
                    } elseif ('time' === $rgxp) {
                        $result = Date::parse($GLOBALS['objPage']->timeFormat ?? $GLOBALS['TL_CONFIG']['timeFormat'] ?? '', $value);
                    } elseif ('datim' === $rgxp) {
                        $result = Date::parse($GLOBALS['objPage']->datimFormat ?? $GLOBALS['TL_CONFIG']['datimFormat'] ?? '', $value);
                    } elseif (\is_array($value)) {
                        $result = implode(', ', $value);
                    } elseif (\is_array($opts) && ArrayUtil::isAssoc($opts)) {
                        $result = $opts[$value] ?? $value;
                    } elseif (\is_array($rfrc)) {
                        $result = isset($rfrc[$value]) ? (\is_array($rfrc[$value]) ? $rfrc[$value][0] : $rfrc[$value]) : $value;
                    } else {
                        $result = $value;
                    }

                    // Convert special characters (see #1890)
                    $result = StringUtil::specialchars($result);
                }
                break;

            // Closing link tag
            case 'email_close':
                $result = '</a>';
                break;

            // Insert article
            case 'insert_article':
                if (false !== ($strOutput = Controller::getArticle($insertTag->getParameters()->get(0), false, true))) {
                    $result = ltrim($strOutput);
                } else {
                    $result = '<p class="error">'.sprintf($GLOBALS['TL_LANG']['MSC']['invalidPage'], $insertTag->getParameters()->get(0)).'</p>';
                }
                break;

            // Insert content element
            case 'insert_content':
                $result = Controller::getContentElement($insertTag->getParameters()->get(0));
                break;

            // Insert module
            case 'insert_module':
                $result = Controller::getFrontendModule($insertTag->getParameters()->get(0));
                break;

            // Insert form
            case 'insert_form':
                $result = Controller::getForm($insertTag->getParameters()->get(0));
                break;

            // Article
            case 'article':
            case 'article_open':
            case 'article_url':
                $objArticle = ArticleModel::findByIdOrAlias($insertTag->getParameters()->get(0));

                if (!$objArticle instanceof ArticleModel) {
                    break;
                }

                $strTarget = \in_array('blank', \array_slice($insertTag->getParameters()->all(), 1), true) ? ' target="_blank" rel="noreferrer noopener"' : '';
                $strUrl = '';

                try {
                    $blnAbsolute = \in_array('absolute', \array_slice($insertTag->getParameters()->all(), 1), true);
                    $strUrl = $this->container->get('contao.routing.content_url_generator')->generate($objArticle);

                    if (!$blnAbsolute) {
                        $strUrl = UrlUtil::makeAbsolutePath($strUrl, Environment::get('base'));
                    }
                } catch (ExceptionInterface) {
                    // Ignore routing exception
                }

                // Replace the tag
                switch ($insertTag->getName()) {
                    case 'article':
                        $result = sprintf('<a href="%s" title="%s"%s>%s</a>', $strUrl, StringUtil::specialcharsAttribute($objArticle->title), $strTarget, $objArticle->title);
                        break;

                    case 'article_open':
                        $result = sprintf('<a href="%s" title="%s"%s>', $strUrl, StringUtil::specialcharsAttribute($objArticle->title), $strTarget);
                        break;

                    case 'article_url':
                        $result = $strUrl;
                        break;
                }
                break;

            // Article title
            case 'article_title':
                if ($objArticle = ArticleModel::findByIdOrAlias($insertTag->getParameters()->get(0))) {
                    $result = StringUtil::specialcharsAttribute($objArticle->title);
                }
                break;

            // Article teaser
            case 'article_teaser':
                if ($objTeaser = ArticleModel::findByIdOrAlias($insertTag->getParameters()->get(0))) {
                    $result = $objTeaser->teaser;
                }
                break;

            // Last update
            case 'last_update':
                $outputType = OutputType::text;

                $strQuery = 'SELECT MAX(tstamp) AS tc';
                $bundles = $this->container->getParameter('kernel.bundles');

                if (isset($bundles['ContaoNewsBundle'])) {
                    $strQuery .= ', (SELECT MAX(tstamp) FROM tl_news) AS tn';
                }

                if (isset($bundles['ContaoCalendarBundle'])) {
                    $strQuery .= ', (SELECT MAX(tstamp) FROM tl_calendar_events) AS te';
                }

                $strQuery .= ' FROM tl_content';
                $objUpdate = Database::getInstance()->query($strQuery);

                if ($objUpdate->numRows) {
                    $result = Date::parse($insertTag->getParameters()->get(0) ?? ($GLOBALS['objPage']->datimFormat ?? $GLOBALS['TL_CONFIG']['datimFormat'] ?? ''), max($objUpdate->tc, $objUpdate->tn, $objUpdate->te));
                }
                break;

            // Version
            case 'version':
                $result = ContaoCoreBundle::getVersion();
                break;

            // Environment
            case 'env':
                switch ($insertTag->getParameters()->get(0)) {
                    case 'host':
                        $result = Idna::decode(Environment::get('host'));
                        break;

                    case 'http_host':
                        $result = Idna::decode(Environment::get('httpHost'));
                        break;

                    case 'url':
                        $result = Idna::decode(Environment::get('url'));
                        break;

                    // As the "env::path" insert tag returned the base URL ever since, we
                    // keep it as an alias to the "env::base" tag. Use "env::base_path" to
                    // output the actual base path.
                    case 'path':
                    case 'base':
                        $result = Idna::decode(Environment::get('base'));
                        break;

                    case 'request':
                        $result = Environment::get('indexFreeRequest');
                        break;

                    case 'ip':
                        $result = Environment::get('ip');
                        break;

                    case 'referer':
                        $result = Controller::getReferer(true);
                        break;

                    case 'files_url':
                        $result = $this->container->get('contao.assets.files_context')->getStaticUrl();
                        break;

                    case 'assets_url':
                    case 'plugins_url':
                    case 'script_url':
                        $result = $this->container->get('contao.assets.assets_context')->getStaticUrl();
                        break;

                    case 'base_url':
                        $result = $this->container->get('request_stack')->getCurrentRequest()->getBaseUrl();
                        break;

                    case 'base_path':
                        $result = $this->container->get('request_stack')->getCurrentRequest()->getBasePath();
                        break;
                }

                $result = StringUtil::specialcharsUrl($result);
                break;

            // Page
            case 'page':
                $property = $insertTag->getParameters()->get(0);

                if ($GLOBALS['objPage']) {
                    if (!$GLOBALS['objPage']->parentPageTitle && 'parentPageTitle' === $property) {
                        $property = 'parentTitle';
                    } elseif (!$GLOBALS['objPage']->mainPageTitle && 'mainPageTitle' === $property) {
                        $property = 'mainTitle';
                    }
                }

                $responseContext = $this->container->get('contao.routing.response_context_accessor')->getResponseContext();

                if ($responseContext?->has(HtmlHeadBag::class) && \in_array($property, ['pageTitle', 'description'], true)) {
                    $htmlHeadBag = $responseContext->get(HtmlHeadBag::class);

                    $result = match ($property) {
                        'pageTitle' => htmlspecialchars($htmlHeadBag->getTitle()),
                        'description' => htmlspecialchars($htmlHeadBag->getMetaDescription()),
                    };
                } elseif ($GLOBALS['objPage']) {
                    // Do not use StringUtil::specialchars() here (see #4687)
                    if (!\in_array($property, ['title', 'parentTitle', 'mainTitle', 'rootTitle', 'pageTitle', 'parentPageTitle', 'mainPageTitle', 'rootPageTitle'], true)) {
                        $outputType = OutputType::text;
                    }

                    $result = $GLOBALS['objPage']->{$property};
                }
                break;

            // Abbreviations
            case 'abbr':
            case 'acronym':
                if (!empty($insertTag->getParameters()->get(0))) {
                    $result = '<abbr title="'.StringUtil::specialchars($insertTag->getParameters()->get(0)).'">';
                } else {
                    $result = '</abbr>';
                }
                break;

            // Images
            case 'figure':
                // Expected format: {{figure::<from>[?<key>=<value>,[&<key>=<value>]*]}}
                [$from, $configuration] = $this->parseUrlWithQueryString((string) $insertTag->getParameters()->get(0));

                if (null === $from || 1 !== \count($insertTag->getParameters()->all()) || Validator::isInsecurePath($from) || Path::isAbsolute($from)) {
                    break;
                }

                $size = $configuration['size'] ?? null;
                $template = $configuration['template'] ?? '@ContaoCore/Image/Studio/figure.html.twig';

                unset($configuration['size'], $configuration['template']);

                // Render the figure
                $figureRenderer = $this->container->get('contao.image.studio.figure_renderer');

                try {
                    $result = $figureRenderer->render($from, $size, $configuration, $template) ?? '';
                } catch (\Throwable) {
                    // Ignore
                }
                break;

            case 'image':
            case 'picture':
                $width = null;
                $height = null;
                $alt = '';
                $class = '';
                $rel = '';
                $strFile = $insertTag->getParameters()->get(0);
                $mode = '';
                $size = null;
                $strTemplate = 'picture_default';

                // Take arguments
                if (str_contains($insertTag->getParameters()->get(0), '?')) {
                    $arrChunks = explode('?', urldecode($insertTag->getParameters()->get(0)), 2);
                    $strSource = StringUtil::decodeEntities($arrChunks[1]);
                    $arrParams = explode('&', $strSource);

                    foreach ($arrParams as $strParam) {
                        [$key, $value] = explode('=', $strParam);

                        switch ($key) {
                            case 'width':
                                $width = (int) $value;
                                break;

                            case 'height':
                                $height = (int) $value;
                                break;

                            case 'alt':
                                $alt = $value;
                                break;

                            case 'class':
                                $class = $value;
                                break;

                            case 'rel':
                                $rel = $value;
                                break;

                            case 'mode':
                                $mode = $value;
                                break;

                            case 'size':
                                $size = is_numeric($value) ? (int) $value : $value;
                                break;

                            case 'template':
                                $strTemplate = preg_replace('/[^a-z0-9_]/i', '', $value);
                                break;
                        }
                    }

                    $strFile = $arrChunks[0];
                }

                if (Validator::isUuid($strFile)) {
                    // Handle UUIDs
                    if (!$objFile = FilesModel::findByUuid($strFile)) {
                        break;
                    }

                    $strFile = $objFile->path;
                } elseif (is_numeric($strFile)) {
                    // Handle numeric IDs (see #4805)
                    if (!$objFile = FilesModel::findByPk($strFile)) {
                        break;
                    }

                    $strFile = $objFile->path;
                } elseif (Validator::isInsecurePath($strFile)) {
                    throw new \RuntimeException('Invalid path '.$strFile);
                }

                // Use the alternative text from the image metadata if none is given
                if (!$alt && ($objFile = FilesModel::findByPath($strFile))) {
                    $arrMeta = Frontend::getMetaData($objFile->meta, $GLOBALS['objPage']->language ?? $GLOBALS['TL_LANGUAGE']);

                    if (isset($arrMeta['alt'])) {
                        $alt = $arrMeta['alt'];
                    }
                }

                // Generate the thumbnail image
                try {
                    if ('image' === $insertTag->getName()) {
                        $dimensions = '';
                        $src = $this->container->get('contao.image.factory')->create($this->container->getParameter('kernel.project_dir').'/'.rawurldecode($strFile), [$width, $height, $mode])->getUrl($this->container->getParameter('kernel.project_dir'));
                        $objFile = new File(rawurldecode($src));

                        // Add the image dimensions
                        if (isset($objFile->imageSize[0], $objFile->imageSize[1])) {
                            $dimensions = ' width="'.$objFile->imageSize[0].'" height="'.$objFile->imageSize[1].'"';
                        }

                        $result = '<img src="'.StringUtil::specialcharsUrl(Controller::addFilesUrlTo($src)).'" '.$dimensions.' alt="'.StringUtil::specialcharsAttribute($alt).'"'.($class ? ' class="'.StringUtil::specialcharsAttribute($class).'"' : '').'>';
                    } else {
                        $staticUrl = $this->container->get('contao.assets.files_context')->getStaticUrl();
                        $picture = $this->container->get('contao.image.picture_factory')->create($this->container->getParameter('kernel.project_dir').'/'.$strFile, $size);

                        $data = [
                            'img' => $picture->getImg($this->container->getParameter('kernel.project_dir'), $staticUrl),
                            'sources' => $picture->getSources($this->container->getParameter('kernel.project_dir'), $staticUrl),
                            'alt' => StringUtil::specialcharsAttribute($alt),
                            'class' => StringUtil::specialcharsAttribute($class),
                        ];

                        $pictureTemplate = new FrontendTemplate($strTemplate);
                        $pictureTemplate->setData($data);

                        $result = $pictureTemplate->parse();
                    }

                    // Add a lightbox link
                    if ($rel) {
                        $result = '<a href="'.StringUtil::specialcharsUrl(Controller::addFilesUrlTo($strFile)).'"'.($alt ? ' title="'.StringUtil::specialcharsAttribute($alt).'"' : '').' data-lightbox="'.StringUtil::specialcharsAttribute($rel).'">'.$result.'</a>';
                    }
                } catch (\Exception) {
                    $result = '';
                }
                break;

            // Files (UUID or template path)
            case 'file':
                $uuid = $insertTag->getParameters()->get(0);

                if (Validator::isUuid($uuid) && ($objFile = FilesModel::findByUuid($uuid))) {
                    $result = System::getContainer()->get('contao.assets.files_context')->getStaticUrl().System::urlEncode($objFile->path);
                    break;
                }

                trigger_deprecation('contao/core-bundle', '5.0', 'Using the file insert tag to include templates has been deprecated and will no longer work in Contao 6. Use the "Template" content element instead.');

                $arrGet = $_GET;
                $strFile = $insertTag->getParameters()->get(0);

                // Take arguments and add them to the $_GET array
                if (str_contains($strFile, '?')) {
                    $arrChunks = explode('?', urldecode($strFile));
                    $strSource = StringUtil::decodeEntities($arrChunks[1]);
                    $arrParams = explode('&', $strSource);

                    foreach ($arrParams as $strParam) {
                        $arrParam = explode('=', $strParam);
                        $_GET[$arrParam[0]] = $arrParam[1];
                    }

                    $strFile = $arrChunks[0];
                }

                // Check the path
                if (Validator::isInsecurePath($strFile)) {
                    throw new \RuntimeException('Invalid path '.$strFile);
                }

                // Include .php, .tpl, .xhtml and .html5 files
                if (preg_match('/\.(php|tpl|xhtml|html5)$/', $strFile) && (new Filesystem())->exists($this->container->getParameter('kernel.project_dir').'/templates/'.$strFile)) {
                    ob_start();

                    try {
                        include $this->container->getParameter('kernel.project_dir').'/templates/'.$strFile;
                        $result = ob_get_contents();
                    } finally {
                        ob_end_clean();
                    }
                }

                $_GET = $arrGet;
                break;
        }

        if (\is_array($result)) {
            $result = ArrayUtil::flattenToString($result);
        }

        return new InsertTagResult((string) $result, $outputType);
    }

    /**
     * @return array{string|null, array}
     */
    private function parseUrlWithQueryString(string $url): array
    {
        // Restore = and &
        $url = str_replace(['&#61;', '&amp;'], ['=', '&'], $url);

        $base = parse_url($url, PHP_URL_PATH) ?: null;
        $query = parse_url($url, PHP_URL_QUERY) ?: '';

        parse_str($query, $attributes);

        // Cast and encode values
        array_walk_recursive(
            $attributes,
            static function (&$value): void {
                if (is_numeric($value)) {
                    $value = (int) $value;

                    return;
                }

                $value = StringUtil::specialcharsAttribute($value);
            },
        );

        return [$base, $attributes];
    }
}
