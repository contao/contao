<?= "<?php\n" ?>

declare(strict_types=1);

namespace App\EventListener;

<?php foreach ($uses as $use): ?>
use <?= $use ?>;
<?php endforeach; ?>
<?php if ($use_attributes): ?>
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
<?php else: ?>
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;
<?php endif; ?>

<?php if ($use_attributes): ?>
#[AsEventListener("<?= $event ?>")]
<?php else: ?>
/**
 * @ServiceTag("kernel.event_listener", event=<?= $event ?>)
 */
<?php endif; ?>
class <?= $className."\n" ?>
{
    <?= $signature."\n" ?>
    {
        <?= $body."\n" ?>
    }
}
