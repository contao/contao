<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Backend;

use Contao\E2eTesting\Browser\BackendBrowser;
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Playwright\Locator\LocatorInterface;

class ImportantPartTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    private const string PREVIEW = '.tl_edit_preview[data-controller="contao--important-part"]';

    private const int IMAGE_PADDING_AND_BORDER = 7;

    public function testDrawingSetsTheImportantPart(): void
    {
        $backend = $this->openFile();

        $this->assertSame([0.0, 0.0, 0.0, 0.0], $this->values($backend));
        $this->assertFalse($this->part($backend)->isVisible());

        // The input fields are replaced by the preview
        $this->assertFalse($backend->page()->locator('#ctrl_importantPartX')->isVisible());

        // Draw from the bottom right to the top left of the second quarter
        $this->drawImportantPart($backend, [0.75, 0.5], [0.25, 0.25]);

        $this->assertImportantPart([0.25, 0.25, 0.5, 0.25], $this->values($backend));
        $this->assertTrue($this->part($backend)->isVisible());

        // The selection is drawn at the same position
        $image = $this->imageContentBox($backend);
        $part = $this->part($backend)->boundingBox();

        // Allow for the selection border and subpixel rounding
        $this->assertEqualsWithDelta($image['x'] + 0.25 * $image['width'], $part['x'], 2);
        $this->assertEqualsWithDelta($image['y'] + 0.25 * $image['height'], $part['y'], 2);
        $this->assertEqualsWithDelta(0.5 * $image['width'], $part['width'], 2);
        $this->assertEqualsWithDelta(0.25 * $image['height'], $part['height'], 2);

        $backend->submitForm('Save');
        $backend->waitFor(self::PREVIEW.'.tl_edit_preview_enabled');

        $this->assertImportantPart([0.25, 0.25, 0.5, 0.25], $this->values($backend));
        $this->assertTrue($this->part($backend)->isVisible());
    }

    public function testThePreviewIsInitializedOnceWhenRestoredFromTheTurboCache(): void
    {
        $backend = $this->openFile();
        $this->drawImportantPart($backend, [0.25, 0.25], [0.75, 0.75]);

        $backend->waitForTurboNavigation(static fn () => $backend->page()->evaluate('() => Turbo.visit("/contao?do=files")'));
        $backend->waitForTurboNavigation(static fn () => $backend->page()->evaluate('() => history.back()'));
        $backend->waitFor(self::PREVIEW.'.tl_edit_preview_enabled');

        $this->assertSame(1, $backend->page()->locator(self::PREVIEW.' .tl_edit_preview_important_part')->count());
        $this->assertImportantPart([0.25, 0.25, 0.5, 0.5], $this->values($backend));

        // Drawing still works after the restore
        $this->drawImportantPart($backend, [0.5, 0.5], [1, 1]);

        $this->assertImportantPart([0.5, 0.5, 0.5, 0.5], $this->values($backend));
    }

    public function testDrawingIgnoresOtherPointers(): void
    {
        $backend = $this->openFile();

        $widths = $backend->page()->locator(self::PREVIEW)->evaluate(
            <<<'JS'
                (preview) => {
                    const rect = preview.querySelector('img').getBoundingClientRect();
                    const width = () => document.querySelector('#ctrl_importantPartWidth').value;
                    const send = (type, pointerId, position) => preview.dispatchEvent(new PointerEvent(type, {
                        bubbles: true,
                        cancelable: true,
                        pointerId,
                        pointerType: 'touch',
                        button: 0,
                        clientX: rect.left + position,
                        clientY: rect.top + position,
                    }));

                    send('pointerdown', 1, 30);
                    send('pointermove', 1, 60);
                    const before = width();
                    send('pointerdown', 2, 80);
                    send('pointermove', 2, 90);
                    send('pointerup', 2, 90);
                    const after = width();
                    send('pointerup', 1, 90);

                    return [before, after, width()];
                }
                JS,
        );

        $this->assertGreaterThan(0, (float) $widths[0]);
        $this->assertSame($widths[0], $widths[1]);
        $this->assertEqualsWithDelta(2 * (float) $widths[0], (float) $widths[2], 0.01);
    }

    private function openFile(): BackendBrowser
    {
        $this->registerDummyImage();

        $backend = $this->login();
        $backend->visit('/contao?do=files&act=edit&id='.self::DUMMY_IMAGE);
        $backend->waitFor(self::PREVIEW.'.tl_edit_preview_enabled');

        return $backend;
    }

    private function drawImportantPart(BackendBrowser $backend, array $from, array $to): void
    {
        $box = $this->imageContentBox($backend);

        $mouse = $backend->page()->mouse();
        $mouse->move($box['x'] + $from[0] * $box['width'], $box['y'] + $from[1] * $box['height']);
        $mouse->down();
        $mouse->move($box['x'] + $to[0] * $box['width'], $box['y'] + $to[1] * $box['height'], ['steps' => 5]);
        $mouse->up();
    }

    private function imageContentBox(BackendBrowser $backend): array
    {
        $box = $backend->page()->locator(self::PREVIEW.' img')->boundingBox();

        return [
            'x' => $box['x'] + self::IMAGE_PADDING_AND_BORDER,
            'y' => $box['y'] + self::IMAGE_PADDING_AND_BORDER,
            'width' => $box['width'] - 2 * self::IMAGE_PADDING_AND_BORDER,
            'height' => $box['height'] - 2 * self::IMAGE_PADDING_AND_BORDER,
        ];
    }

    private function values(BackendBrowser $backend): array
    {
        return array_map(
            static fn (string $field): float => (float) $backend->page()->locator('#ctrl_importantPart'.$field)->inputValue(),
            ['X', 'Y', 'Width', 'Height'],
        );
    }

    private function assertImportantPart(array $expected, array $actual): void
    {
        foreach ($expected as $i => $value) {
            $this->assertEqualsWithDelta($value, $actual[$i], 0.01);
        }
    }

    private function part(BackendBrowser $backend): LocatorInterface
    {
        return $backend->page()->locator(self::PREVIEW.' .tl_edit_preview_important_part');
    }
}
