<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\Model;
use Contao\Template;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * This class is a simple container object for template data.
 *
 * @todo Remove the base class in Contao 6.0
 */
final class FragmentTemplate extends Template
{
    /**
     * @var array<string,mixed>
     */
    private array $context = [];

    /**
     * @param \Closure(self, Response|null):Response $onGetResponse
     *
     * @internal
     */
    public function __construct(private string $templateName, private \Closure $onGetResponse)
    {
        // Do not call parent constructor
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value): void
    {
        $this->set($key, $value);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     */
    public function __isset($key): bool
    {
        return $this->has($key);
    }

    public function __call($strKey, $arrParams): never
    {
        self::throwOnAccess();
    }

    public function set(string $key, mixed $value): void
    {
        $this->context[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $this->context[$key] ?? throw new \RuntimeException(sprintf('Key "%s" does not exist.', $key));
    }

    public function has(string $key): bool
    {
        return isset($this->context[$key]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData($data): void
    {
        $this->context = $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->context;
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->templateName = $name;
    }

    public function getName(): string
    {
        return $this->templateName;
    }

    /**
     * Renders the template and returns a new Response, that has the rendered
     * output set as content, as well as the appropriate headers that allows
     * our SubrequestCacheSubscriber to merge it with others of the same page.
     *
     * For modern fragments, the behavior is identical to calling render() on
     * the AbstractFragmentController. Like with render(), you can pass a
     * prebuilt Response if you want to have full control - no headers will be
     * set then.
     */
    public function getResponse(Response|null $preBuiltResponse = null): Response
    {
        return ($this->onGetResponse)($this, $preBuiltResponse);
    }

    // We need to extend from the legacy Template class to keep existing type
    // hints working. In the future, when people migrated their usages, we will
    // drop the base class and the following overrides, that are only there to
    // prevent usage of the base class functionalities.

    /**
     * @internal
     */
    public static function getContainer(): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function setContainer(ContainerInterface $container): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function inherit(): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function getTemplate($strTemplate): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function getTemplateGroup($strPrefix, array $arrAdditionalMapper = [], $strDefaultTemplate = ''): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function getFrontendModule($intId, $strColumn = 'main'): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function getArticle($varId, $blnMultiMode = false, $blnIsInsertTag = false, $strColumn = 'main'): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function getContentElement($intId, $strColumn = 'main'): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function getForm($varId, $strColumn = 'main', $blnModule = false): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function getPageStatusIcon($objPage): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function isVisibleElement(Model $objElement): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function replaceDynamicScriptTags($strBuffer): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function reload(): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function redirect($strLocation, $intStatus = 303): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function convertRelativeUrls($strContent, $strBase = '', $blnHrefOnly = false): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function sendFileToBrowser($strFile, $inline = false): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function loadDataContainer($strTable): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function resetControllerCache(): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function addEnclosuresToTemplate($objTemplate, $arrItem, $strKey = 'enclosure'): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function addStaticUrlTo($script, ContaoContext $context = null): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function addAssetsUrlTo($script): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function addFilesUrlTo($script): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function parse(): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function addToUrl($strRequest, $blnIgnoreParams = false, $arrUnset = []): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function importStatic($strClass, $strKey = null, $blnForce = false): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function getReferer($blnEncodeAmpersands = false, $strTable = null): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function loadLanguageFile($strName, $strLanguage = null, $blnNoCache = false): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function isInstalledLanguage($strLanguage): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function urlEncode($strPath): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function setCookie($strName, $varValue, $intExpires, $strPath = null, $strDomain = null, $blnSecure = null, $blnHttpOnly = false): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function getReadableSize($intSize, $intDecimals = 1): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function getFormattedNumber($varNumber, $intDecimals = 2): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function anonymizeIp($strIp): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function setFormat($strFormat): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function getFormat(): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function dumpTemplateVars(): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function route($strName, $arrParams = []): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function previewRoute($strName, $arrParams = []): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function trans($strId, array $arrParams = [], $strDomain = 'contao_default', $locale = null): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function rawPlainText(string $value, bool $removeInsertTags = false): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function rawHtmlToPlainText(string $value, bool $removeInsertTags = false): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function addSchemaOrg(array $jsonLd): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function figure($from, $size, $configuration = [], $template = 'image'): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function asset($path, $packageName = null): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function assetVersion($path, $packageName = null): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function param($strKey): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function prefixUrl($strKey): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function minifyHtml($strHtml): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function generateStyleTag($href, $media = null, $mtime = false): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function generateInlineStyle($script): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function generateScriptTag($src, $async = false, $mtime = false, $hash = null, $crossorigin = null, $referrerpolicy = null, $defer = false): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function generateInlineScript($script): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public static function generateFeedTag($href, $format, $title): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function setDebug(bool $debug = null): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function extend($name): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function parent(): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function block($name): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function endblock(): never
    {
        self::throwOnAccess();
    }

    /**
     * @internal
     */
    public function insert($name, array $data = null): never
    {
        self::throwOnAccess();
    }

    private static function throwOnAccess(): never
    {
        $function = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];

        throw new \LogicException(sprintf('Calling the "%s()" function on a FragmentTemplate is not allowed. Set template data instead and optionally output it with getResponse().', $function));
    }
}
