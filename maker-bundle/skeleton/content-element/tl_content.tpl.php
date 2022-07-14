<?php if (!$append): ?>
<?= "<?php\n" ?>

declare(strict_types=1);
<?php endif; ?>

$GLOBALS['TL_DCA']['tl_content']['palettes']['<?= $element_name ?>'] = '
    {type_legend},type,headline;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},cssID;
    {invisible_legend:hide},invisible,start,stop
';
