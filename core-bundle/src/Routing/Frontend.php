<?php

namespace Contao\CoreBundle\Routing;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Input;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;

class Frontend
{
    /**
     * @var string
     */
    private $urlSuffix;
    /**
     * @var bool
     */
    private $prependLocale;
    /**
     * @var bool
     */
    private $folderUrl;
    /**
     * @var bool
     */
    private $useAutoItem;

    /**
     * @var PageModel
     */
    private $pageAdapter;

    /**
     * @var Input
     */
    private $inputAdapter;


    public function __construct($pageAdapter, $inputAdapter, string $urlSuffix, bool $prependLocale, bool $folderUrl, bool $useAutoItem)
    {
        $this->pageAdapter = $pageAdapter;
        $this->inputAdapter = $inputAdapter;
        $this->urlSuffix = $urlSuffix;
        $this->prependLocale = $prependLocale;
        $this->folderUrl = $folderUrl;
        $this->useAutoItem = $useAutoItem;
    }

    /**
     * Split the current request into fragments, strip the URL suffix, recreate the $_GET array and return the page ID
     *
     * @return mixed
     */
    public function getPageIdFromUrl(Request $request)
    {
//        $strRequest = Environment::get('relativeRequest');
        $strRequest = substr($request->getPathInfo(), 1);
//        $strRequest = 0 === strncmp($strRequest, '/', 1) ? substr($strRequest, 1) : $strRequest;

        if ($strRequest == '') {
            return null;
        }

        // Get the request without the query string
        list($strRequest) = explode('?', $strRequest, 2);

        // URL decode here (see #6232)
        $strRequest = rawurldecode($strRequest);

        // The request string must not contain "auto_item" (see #4012)
        if (strpos($strRequest, '/auto_item/') !== false) {
            throw new \RuntimeException('The request string must not contain "auto_item"');
        }

        // Remove the URL suffix if not just a language root (e.g. en/) is requested
        if ($strRequest != '' && (!$this->prependLocale || !preg_match('@^[a-z]{2}(-[A-Z]{2})?/$@', $strRequest))) {
            $intSuffixLength = \strlen($this->urlSuffix);

            // Return false if the URL suffix does not match (see #2864)
            if ($intSuffixLength > 0) {
                if (substr($strRequest, -$intSuffixLength) != $this->urlSuffix) {
                    throw new \RuntimeException('The URL suffix does not match');
                }

                $strRequest = substr($strRequest, 0, -$intSuffixLength);
            }
        }

        // Extract the language
        if ($this->prependLocale) {
            $arrMatches = array();

            // Use the matches instead of substr() (thanks to Mario Müller)
            if (preg_match('@^([a-z]{2}(-[A-Z]{2})?)/(.*)$@', $strRequest, $arrMatches)) {
                $this->inputAdapter->setGet('language', $arrMatches[1]);

                // Trigger the root page if only the language was given
                if ($arrMatches[3] == '') {
                    return null;
                }

                $strRequest = $arrMatches[3];
            } else {
                throw new \RuntimeException('Language not provided');
            }
        }

        $arrFragments = null;

        // Use folder-style URLs
        if ($this->folderUrl && strpos($strRequest, '/') !== false) {
            $strAlias = $strRequest;
            $arrOptions = array($strAlias);

            // Compile all possible aliases by applying dirname() to the request (e.g. news/archive/item, news/archive, news)
            while ($strAlias != '/' && strpos($strAlias, '/') !== false) {
                $strAlias = \dirname($strAlias);
                $arrOptions[] = $strAlias;
            }

            // Check if there are pages with a matching alias
            $objPages = $this->pageAdapter->findByAliases($arrOptions);

            if ($objPages !== null) {
                $arrPages = array();

                // Order by domain and language
                while ($objPages->next()) {
                    /** @var PageModel $objModel */
                    $objModel = $objPages->current();
                    $objPage  = $objModel->loadDetails();

                    $domain = $objPage->domain ?: '*';
                    $arrPages[$domain][$objPage->rootLanguage][] = $objPage;

                    // Also store the fallback language
                    if ($objPage->rootIsFallback) {
                        $arrPages[$domain]['*'][] = $objPage;
                    }
                }

//                $strHost = \Environment::get('host');
                $strHost = $request->getHost();

                // Look for a root page whose domain name matches the host name
                if (isset($arrPages[$strHost])) {
                    $arrLangs = $arrPages[$strHost];
                } else {
                    $arrLangs = $arrPages['*'] ?: array(); // empty domain
                }

                $arrAliases = array();

                if (!$this->prependLocale) {
                    // Use the first result (see #4872)
                    $arrAliases = current($arrLangs);
                } elseif (($lang = $this->inputAdapter->get('language')) && isset($arrLangs[$lang])) {
                    // Try to find a page matching the language parameter
                    $arrAliases = $arrLangs[$lang];
                }

                // Return if there are no matches
                if (empty($arrAliases)) {
                    throw new \RuntimeException('No matches for folder URL');
                }

                $objPage = $arrAliases[0];

                if ($strRequest == $objPage->alias) {
                    // The request consists of the alias only
                    $arrFragments = array($strRequest);
                } else {
                    // Remove the alias from the request string, explode it and then re-insert the alias at the beginning
                    $arrFragments = explode('/', substr($strRequest, \strlen($objPage->alias) + 1));
                    array_unshift($arrFragments, $objPage->alias);
                }
            }
        }

        // If folderUrl is deactivated or did not find a matching page
        if ($arrFragments === null) {
            if ($strRequest == '/') {
                throw new \RuntimeException('Did not find a matching page');
            } else {
                $arrFragments = explode('/', $strRequest);
            }
        }

        // Add the second fragment as auto_item if the number of fragments is even
        if ($this->useAutoItem && \count($arrFragments) % 2 == 0) {
            array_insert($arrFragments, 1, array('auto_item'));
        }

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['getPageIdFromUrl']) && \is_array($GLOBALS['TL_HOOKS']['getPageIdFromUrl']))
        {
            foreach ($GLOBALS['TL_HOOKS']['getPageIdFromUrl'] as $callback)
            {
                $arrFragments = System::importStatic($callback[0])->{$callback[1]}($arrFragments);
            }
        }

        // Return if the alias is empty (see #4702 and #4972)
        if ($arrFragments[0] == '' && \count($arrFragments) > 1) {
            throw new \RuntimeException('The alias is empty');
        }

        // Add the fragments to the $_GET array
        for ($i=1, $c=\count($arrFragments); $i<$c; $i+=2) {
            // Skip key value pairs if the key is empty (see #4702)
            if ($arrFragments[$i] == '') {
                continue;
            }

            // Return false if there is a duplicate parameter (duplicate content) (see #4277)
            if (isset($_GET[$arrFragments[$i]])) {
                throw new \RuntimeException('Duplicate parameter in query');
            }

            // Return false if the request contains an auto_item keyword (duplicate content) (see #4012)
            if ($this->useAutoItem && \in_array($arrFragments[$i], $GLOBALS['TL_AUTO_ITEM'])) {
                throw new \RuntimeException('Request contains an auto_item keyword');
            }

            $this->inputAdapter->setGet(urldecode($arrFragments[$i]), urldecode($arrFragments[$i+1]), true);
        }

        return $arrFragments[0] ?: null;
    }

}
