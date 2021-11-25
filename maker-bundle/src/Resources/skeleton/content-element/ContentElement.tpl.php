<?= "<?php\n" ?>

declare(strict_types=1);

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
<?php if (PHP_VERSION_ID >= 80000): ?>
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
<?php else: ?>
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
<?php endif; ?>
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

<?php if (PHP_VERSION_ID >= 80000): ?>
#[AsContentElement("<?= $elementName ?>", "<?= $category ?>")]
<?php else: ?>
/**
 * @ContentElement("<?= $elementName ?>", category="<?= $category ?>")
 */
<?php endif; ?>
class <?= $className ?> extends AbstractContentElementController
{
    protected function getResponse(Template $template, ContentModel $model, Request $request): ?Response
    {
        return $template->getResponse();
    }
}
