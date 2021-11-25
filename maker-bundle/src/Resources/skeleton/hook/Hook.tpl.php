<?= "<?php\n" ?>

declare(strict_types=1);

namespace App\EventListener;

<?php if (PHP_VERSION_ID >= 80000): ?>
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
<?php else: ?>
use Contao\CoreBundle\ServiceAnnotation\Hook;
<?php endif; ?>
<?php foreach ($uses as $use): ?>
use <?= $use ?>;
<?php endforeach; ?>

<?php if (PHP_VERSION_ID >= 80000): ?>
#[AsHook("<?= $hook ?>")]
<?php else: ?>
/**
 * @Hook("<?= $hook ?>")
 */
<?php endif; ?>
class <?= $className."\n" ?>
{
    <?= $signature."\n" ?>
    {
        <?= $body."\n" ?>
    }
}
