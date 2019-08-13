<?php

namespace PHPSTORM_META {

    registerArgumentsSet('contao_palette_manipulator_positions',
        \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_BEFORE,
        \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER,
        \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND,
        \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_PREPEND
    );

    expectedArguments(\Contao\CoreBundle\DataContainer\PaletteManipulator::addLegend(), 2, argumentsSet('contao_palette_manipulator_positions'));
    expectedArguments(\Contao\CoreBundle\DataContainer\PaletteManipulator::addField(), 2, argumentsSet('contao_palette_manipulator_positions'));

    registerArgumentsSet('contao_log_levels',
        \Contao\CoreBundle\Monolog\ContaoContext::ERROR,
        \Contao\CoreBundle\Monolog\ContaoContext::ACCESS,
        \Contao\CoreBundle\Monolog\ContaoContext::GENERAL,
        \Contao\CoreBundle\Monolog\ContaoContext::FILES,
        \Contao\CoreBundle\Monolog\ContaoContext::CRON,
        \Contao\CoreBundle\Monolog\ContaoContext::FORMS,
        \Contao\CoreBundle\Monolog\ContaoContext::EMAIL,
        \Contao\CoreBundle\Monolog\ContaoContext::CONFIGURATION,
        \Contao\CoreBundle\Monolog\ContaoContext::NEWSLETTER,
        \Contao\CoreBundle\Monolog\ContaoContext::REPOSITORY
    );

    expectedArguments(\Contao\CoreBundle\Monolog\ContaoContext::__construct(), 1, argumentsSet('contao_log_levels'));
    expectedReturnValues(\Contao\CoreBundle\Monolog\ContaoContext::getAction(), argumentsSet('contao_log_levels'));
    expectedArguments(\Contao\System::log(), 2, argumentsSet('contao_log_levels'));

    registerArgumentsSet('contao_dca_files',
        'tl_article',
        'tl_content',
        'tl_files',
        'tl_form',
        'tl_form_field',
        'tl_image_size',
        'tl_image_size_item',
        'tl_layout',
        'tl_log',
        'tl_member',
        'tl_member_group',
        'tl_module',
        'tl_opt_in',
        'tl_opt_in_related',
        'tl_page',
        'tl_search',
        'tl_search_index',
        'tl_settings',
        'tl_style',
        'tl_style_sheet',
        'tl_templates',
        'tl_theme',
        'tl_undo',
        'tl_user',
        'tl_user_group',
        'tl_version',
    );

    expectedArguments(\Contao\Controller::loadDataContainer(), 0, argumentsSet('contao_dca_files'));
    expectedArguments(\Contao\DcaLoader::__construct(), 0, argumentsSet('contao_dca_files'));
    expectedArguments(\Contao\DcaExtractor::__construct(), 0, argumentsSet('contao_dca_files'));

    registerArgumentsSet('contao_language_files',
        'countries',
        'default',
        'exception',
        'explain',
        'languages',
        'modules',
        'tl_article',
        'tl_content',
        'tl_files',
        'tl_form',
        'tl_form_field',
        'tl_image_size',
        'tl_image_size_item',
        'tl_layout',
        'tl_log',
        'tl_maintenance',
        'tl_member',
        'tl_member_group',
        'tl_module',
        'tl_opt_in',
        'tl_opt_in_related',
        'tl_page',
        'tl_style',
        'tl_style_sheet',
        'tl_templates',
        'tl_theme',
        'tl_undo',
        'tl_user',
        'tl_user_group',
    );

    expectedArguments(\Contao\System::loadLanguageFile(), 0, argumentsSet('contao_language_files'));

}
