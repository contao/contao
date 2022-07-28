<?php if (!$append): ?>
<?= "<?php\n" ?>

declare(strict_types=1);
<?php endif; ?>

$GLOBALS['TL_DCA']['tl_module']['palettes']['<?= $element_name ?>'] = '
    {title_legend},name,headline,type;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},cssID
';
