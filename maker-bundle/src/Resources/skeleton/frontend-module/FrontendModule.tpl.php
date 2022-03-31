<?= "<?php\n" ?>

declare(strict_types=1);

namespace App\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
<?php if ($use_attributes): ?>
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
<?php else: ?>
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
<?php endif; ?>
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

<?php if ($use_attributes): ?>
#[AsFrontendModule(category: "<?= $category ?>")]
<?php else: ?>
/**
 * @FrontendModule(category="<?= $category ?>")
 */
<?php endif; ?>
class <?= $className ?> extends AbstractFrontendModuleController
{
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        return $template->getResponse();
    }
}
