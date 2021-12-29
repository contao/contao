<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Config;
use Contao\DataContainer;
use Contao\System;

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

$GLOBALS['TL_DCA']['tl_test'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ctable' => ['tl_article'],
        'enableVersioning' => true,
        'markAsCopy' => 'title',
        'onload_callback' => [
            ['tl_test', 'callback1'],
            ['tl_test', 'callback2'],
        ],
        'oncut_callback' => [
            ['tl_test', 'callback1'],
        ],
        'ondelete_callback' => [
            ['tl_test', 'callback1'],
        ],
        'onsubmit_callback' => [
            ['tl_test', 'callback1'],
        ],
        'oninvalidate_cache_tags_callback' => [
            ['tl_test', 'callback1'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'alias' => 'index',
                'type,dns' => 'index',
                'pid,published,type,start,stop' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_TREE,
            'showRootTrails' => true,
            'icon' => 'pagemounts.svg',
            'paste_button_callback' => ['tl_test', 'pastePage'],
            'panelLayout' => 'filter;search',
        ],
        'label' => [
            'fields' => ['title'],
            'format' => '%s',
            'label_callback' => ['tl_test', 'addIcon'],
        ],
        'global_operations' => [
            'toggleNodes' => [
                'href' => 'ptg=all',
                'class' => 'header_toggle',
                'showOnSelect' => true,
            ],
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
                'button_callback' => ['tl_test', 'editPage'],
            ],
            'copy' => [
                'href' => 'act=paste&amp;mode=copy',
                'icon' => 'copy.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => ['tl_test', 'copyPage'],
            ],
            'copyChilds' => [
                'href' => 'act=paste&amp;mode=copy&amp;childs=1',
                'icon' => 'copychilds.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => ['tl_test', 'copyPageWithSubpages'],
            ],
            'cut' => [
                'href' => 'act=paste&amp;mode=cut',
                'icon' => 'cut.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => ['tl_test', 'cutPage'],
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
                'button_callback' => ['tl_test', 'deletePage'],
            ],
            'toggle' => [
                'icon' => 'visible.svg',
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => ['tl_test', 'toggleIcon'],
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
            'articles' => [
                'href' => 'do=article',
                'icon' => 'article.svg',
            ],
        ],
    ],

    // Select
    'select' => [
        'buttons_callback' => [
            ['tl_test', 'addAliasButton'],
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['type', 'fallback', 'autoforward', 'protected', 'includeLayout', 'includeCache', 'includeChmod', 'enforceTwoFactor'],
        'default' => '{title_legend},title,alias,type',
        'regular' => '{title_legend},title,alias,type;{meta_legend},pageTitle,robots,description,serpPreview;{canonical_legend:hide},canonicalLink,canonicalKeepParams;{protected_legend:hide},protected;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass,sitemap,hide,noSearch,guests,requireItem;{tabnav_legend:hide},tabindex,accesskey;{publish_legend},published,start,stop',
        'forward' => '{title_legend},title,alias,type;{meta_legend},pageTitle,robots;{redirect_legend},jumpTo,redirect;{protected_legend:hide},protected;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass,sitemap,hide,guests;{tabnav_legend:hide},tabindex,accesskey;{publish_legend},published,start,stop',
        'redirect' => '{title_legend},title,alias,type;{meta_legend},pageTitle,robots;{redirect_legend},redirect,url,target;{protected_legend:hide},protected;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass,sitemap,hide,guests;{tabnav_legend:hide},tabindex,accesskey;{publish_legend},published,start,stop',
        'root' => '{title_legend},title,alias,type;{meta_legend},pageTitle;{url_legend},dns,useSSL,urlPrefix,urlSuffix,validAliasCharacters,useFolderUrl;{language_legend},language,fallback,disableLanguageRedirect;{global_legend:hide},mailerTransport,enableCanonical,adminEmail,dateFormat,timeFormat,datimFormat,staticFiles,staticPlugins;{protected_legend:hide},protected;{layout_legend},includeLayout;{twoFactor_legend:hide},enforceTwoFactor;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{publish_legend},published,start,stop',
        'rootfallback' => '{title_legend},title,alias,type;{meta_legend},pageTitle;{url_legend},dns,useSSL,urlPrefix,urlSuffix,validAliasCharacters,useFolderUrl;{language_legend},language,fallback,disableLanguageRedirect;{website_legend:hide},favicon,robotsTxt;{global_legend:hide},mailerTransport,enableCanonical,adminEmail,dateFormat,timeFormat,datimFormat,staticFiles,staticPlugins;{protected_legend:hide},protected;{layout_legend},includeLayout;{twoFactor_legend:hide},enforceTwoFactor;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{publish_legend},published,start,stop',
        'logout' => '{title_legend},title,alias,type;{forward_legend},jumpTo,redirectBack;{protected_legend:hide},protected;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass,sitemap,hide;{tabnav_legend:hide},tabindex,accesskey;{publish_legend},published,start,stop',
        'error_401' => '{title_legend},title,alias,type;{meta_legend},pageTitle,robots,description;{forward_legend},autoforward;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass;{publish_legend},published,start,stop',
        'error_403' => '{title_legend},title,alias,type;{meta_legend},pageTitle,robots,description;{forward_legend},autoforward;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass;{publish_legend},published,start,stop',
        'error_404' => '{title_legend},title,alias,type;{meta_legend},pageTitle,robots,description;{forward_legend},autoforward;{layout_legend:hide},includeLayout;{cache_legend:hide},includeCache;{chmod_legend:hide},includeChmod;{expert_legend:hide},cssClass;{publish_legend},published,start,stop',
    ],

    // Subpalettes
    'subpalettes' => [
        'autoforward' => 'jumpTo',
        'protected' => 'groups',
        'includeLayout' => 'layout',
        'includeCache' => 'clientCache,cache,alwaysLoadFromCache',
        'includeChmod' => 'cuser,cgroup,chmod',
        'enforceTwoFactor' => 'twoFactorJumpTo',
    ],

    // Fields
    'fields' => [
        'id' => [
            'label' => ['ID'],
            'search' => true,
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'sorting' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'tstamp' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'title' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'folderalias', 'doNotCopy' => true, 'maxlength' => 255, 'tl_class' => 'w50 clr'],
            'sql' => "varchar(255) BINARY NOT NULL default ''",
        ],
        'type' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'eval' => ['helpwizard' => true, 'submitOnChange' => true, 'tl_class' => 'w50'],
            'reference' => &$GLOBALS['TL_LANG']['PTY'],
            'sql' => "varchar(64) NOT NULL default 'regular'",
        ],
        'pageTitle' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'language' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 64, 'nospace' => true, 'decodeEntities' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
            'save_callback' => [
                static function ($value) {
                    // Make sure there is at least a basic language
                    if (!preg_match('/^[a-z]{2,}/i', $value)) {
                        throw new RuntimeException($GLOBALS['TL_LANG']['ERR']['language']);
                    }

                    return LocaleUtil::canonicalize($value);
                },
            ],
        ],
        'robots' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'select',
            'options' => ['index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'description' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],
        'serpPreview' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['serpPreview'],
            'exclude' => true,
            'inputType' => 'serpPreview',
            'eval' => ['url_callback' => ['tl_test', 'getSerpUrl'], 'title_tag_callback' => ['tl_test', 'getTitleTag'], 'titleFields' => ['pageTitle', 'title']],
            'sql' => null,
        ],
        'redirect' => [
            'exclude' => true,
            'inputType' => 'select',
            'options' => ['permanent', 'temporary'],
            'eval' => ['tl_class' => 'w50'],
            'reference' => &$GLOBALS['TL_LANG']['tl_test'],
            'sql' => "varchar(32) NOT NULL default 'permanent'",
        ],
        'jumpTo' => [
            'exclude' => true,
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_test.title',
            'eval' => ['fieldType' => 'radio'], // do not set mandatory (see #5453)
            'save_callback' => [
                ['tl_test', 'checkJumpTo'],
            ],
            'sql' => 'int(10) unsigned NOT NULL default 0',
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'redirectBack' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'sql' => "char(1) NOT NULL default ''",
        ],
        'url' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['url'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'dcaPicker' => true, 'tl_class' => 'w50 clr'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'target' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['target'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'dns' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'load_callback' => [
                ['tl_test', 'loadDns'],
            ],
            'save_callback' => [
                ['tl_test', 'checkDns'],
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'staticFiles' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'trailingSlash' => false, 'tl_class' => 'w50'],
            'save_callback' => [
                ['tl_test', 'checkStaticUrl'],
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'staticPlugins' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'trailingSlash' => false, 'tl_class' => 'w50'],
            'save_callback' => [
                ['tl_test', 'checkStaticUrl'],
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'fallback' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'submitOnChange' => true, 'tl_class' => 'w50 clr'],
            'save_callback' => [
                ['tl_test', 'checkFallback'],
            ],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'disableLanguageRedirect' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'favicon' => [
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'extensions' => 'ico,svg'],
            'sql' => 'binary(16) NULL',
        ],
        'robotsTxt' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['doNotCopy' => true, 'decodeEntities' => true],
            'sql' => 'text NULL',
        ],
        'mailerTransport' => [
            'exclude' => true,
            'inputType' => 'select',
            'eval' => ['tl_class' => 'w50', 'includeBlankOption' => true],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'enableCanonical' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'default' => true,
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'canonicalLink' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'dcaPicker' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'canonicalKeepParams' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'adminEmail' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'rgxp' => 'friendly', 'decodeEntities' => true, 'placeholder' => Config::get('adminEmail'), 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'dateFormat' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['helpwizard' => true, 'decodeEntities' => true, 'placeholder' => Contao\Config::get('dateFormat'), 'tl_class' => 'w50'],
            'explanation' => 'dateFormat',
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'timeFormat' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['decodeEntities' => true, 'placeholder' => Contao\Config::get('timeFormat'), 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'datimFormat' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['decodeEntities' => true, 'placeholder' => Contao\Config::get('datimFormat'), 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'validAliasCharacters' => [
            'exclude' => true,
            'inputType' => 'select',
            'options_callback' => static fn () => System::getContainer()->get('contao.slug.valid_characters')->getOptions(),
            'eval' => ['includeBlankOption' => true, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'useFolderUrl' => [
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'urlPrefix' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'folderalias', 'doNotCopy' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(128) BINARY NOT NULL default ''",
        ],
        'urlSuffix' => [
            'inputType' => 'text',
            'eval' => ['nospace' => 'true', 'maxlength' => 16, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'useSSL' => [
            'exclude' => true,
            'inputType' => 'select',
            'options' => ['' => 'http://', '1' => 'https://'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default '1'",
        ],
        'autoforward' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'protected' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'groups' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'foreignKey' => 'tl_member_group.name',
            'eval' => ['mandatory' => true, 'multiple' => true],
            'sql' => 'blob NULL',
            'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
        ],
        'includeLayout' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'layout' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'select',
            'foreignKey' => 'tl_layout.name',
            'options_callback' => ['tl_test', 'getPageLayouts'],
            'eval' => ['chosen' => true, 'tl_class' => 'w50'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'includeCache' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'cache' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'select',
            'options' => [0, 5, 15, 30, 60, 300, 900, 1800, 3600, 10800, 21600, 43200, 86400, 259200, 604800, 2592000, 7776000, 15552000, 31536000],
            'reference' => &$GLOBALS['TL_LANG']['CACHE'],
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'alwaysLoadFromCache' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'clientCache' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'select',
            'options' => [0, 5, 15, 30, 60, 300, 900, 1800, 3600, 10800, 21600, 43200, 86400, 259200, 604800, 2592000],
            'reference' => &$GLOBALS['TL_LANG']['CACHE'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'includeChmod' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'cuser' => [
            'default' => (int) Contao\Config::get('defaultUser'),
            'search' => true,
            'exclude' => true,
            'inputType' => 'select',
            'foreignKey' => 'tl_user.name',
            'eval' => ['mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'cgroup' => [
            'default' => (int) Contao\Config::get('defaultGroup'),
            'search' => true,
            'exclude' => true,
            'inputType' => 'select',
            'foreignKey' => 'tl_user_group.name',
            'eval' => ['mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'chmod' => [
            'default' => Contao\Config::get('defaultChmod'),
            'exclude' => true,
            'inputType' => 'chmod',
            'eval' => ['tl_class' => 'clr'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'noSearch' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'requireItem' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'cssClass' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'sitemap' => [
            'exclude' => true,
            'inputType' => 'select',
            'options' => ['map_default', 'map_always', 'map_never'],
            'eval' => ['maxlength' => 32, 'tl_class' => 'w50'],
            'reference' => &$GLOBALS['TL_LANG']['tl_test'],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'hide' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'guests' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'tabindex' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w50'],
            'sql' => 'smallint(5) unsigned NOT NULL default 0',
        ],
        'accesskey' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alnum', 'maxlength' => 1, 'tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'published' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'start' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'stop' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'enforceTwoFactor' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'twoFactorJumpTo' => [
            'exclude' => true,
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_test.title',
            'eval' => ['fieldType' => 'radio', 'mandatory' => true],
            'save_callback' => [
                ['tl_test', 'checkJumpTo'],
            ],
            'sql' => 'int(10) unsigned NOT NULL default 0',
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
    ],
];

if (!class_exists('tl_test')) {
    class tl_test
    {
        public function callback1(): void
        {
        }

        public function callback2(): void
        {
        }
    }
}
