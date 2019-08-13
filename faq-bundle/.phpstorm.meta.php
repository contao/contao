<?php

namespace PHPSTORM_META {

    registerArgumentsSet('contao_faq_dca_files',
        'tl_faq',
        'tl_faq_category',
    );

    expectedArguments(\Contao\Controller::loadDataContainer(), 0, argumentsSet('contao_faq_dca_files'));
    expectedArguments(\Contao\DcaLoader::__construct(), 0, argumentsSet('contao_faq_dca_files'));
    expectedArguments(\Contao\DcaExtractor::__construct(), 0, argumentsSet('contao_faq_dca_files'));

    registerArgumentsSet('contao_faq_language_files',
        'tl_faq',
        'tl_faq_category',
    );

    expectedArguments(\Contao\System::loadLanguageFile(), 0, argumentsSet('contao_faq_language_files'));
}
