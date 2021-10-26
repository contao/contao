<?= "<?php\n"; ?>

declare(strict_types=1);

namespace App\EventListener;

<?php foreach ($uses as $use): ?>
use <?= $use ?>;
<?php endforeach; ?>
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;

/**
 * @ServiceTag("kernel.event_listener", event=<?= $event ?>)
 */
class <?= $className . "\n" ?>
{
    <?= $signature . "\n" ?>
    {
        // Do something …
    }
}
