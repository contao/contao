<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\String;

use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\String\RecordingHtmlAttributes;
use PHPUnit\Framework\Attributes\DataProvider;

class RecordingHtmlAttributesTest extends AbstractHtmlAttributesTestCase
{
    #[DataProvider('provideMergeTargets')]
    public function testMergesOperationsAfterReading(string $targetClass): void
    {
        $source = new RecordingHtmlAttributes();
        $source->removeClass('foo')->removeStyle('color')->unset('id')->set('title', 'new');

        $this->assertSame(' title="new"', (string) $source);

        $target = new $targetClass(['class' => 'foo bar', 'style' => 'color:red;display:block', 'id' => 'old']);
        $this->assertSame($target, $target->mergeWith($source));

        $source->set('title', 'changed')->removeClass('bar');

        $this->assertSame(' class="bar" style="display: block;" title="new"', (string) $target);
    }

    public static function provideMergeTargets(): iterable
    {
        yield [HtmlAttributes::class];
        yield [RecordingHtmlAttributes::class];
    }

    #[DataProvider('provideMergeTargets')]
    public function testPreservesDeletionsThroughArrayRoundTrip(string $targetClass): void
    {
        $source = new RecordingHtmlAttributes();
        $source->unset('title')->removeClass('foo')->removeStyle('color');

        $expected = ['title' => false, 'class' => false, 'style' => false];
        $this->assertSame($expected, iterator_to_array($source));

        $copy = new RecordingHtmlAttributes(iterator_to_array($source));

        $this->assertSame('', (string) $copy);
        $this->assertSame($expected, iterator_to_array($copy));

        $target = new $targetClass(['title' => 'old', 'class' => 'foo bar', 'style' => 'color: red; display: block;', 'id' => 'keep']);
        $target->mergeWith($copy);

        // The array preserves whole-attribute deletions, not individual class/style removals.
        $this->assertSame(' id="keep"', (string) $target);
    }

    public function testSelfMergeAndConstructionPreserveOperations(): void
    {
        $attributes = new RecordingHtmlAttributes();
        $attributes->removeClass('foo')->addClass('bar');
        $attributes->mergeWith($attributes);

        $copy = new RecordingHtmlAttributes($attributes);
        $attributes->addClass('later');

        $this->assertSame(' class="bar"', (string) new HtmlAttributes($copy));
        $this->assertSame(' class="bar"', (string) new HtmlAttributes(['class' => 'foo'])->mergeWith($copy));
    }

    public function testCapturesValuesAndConditionsImmediately(): void
    {
        $value = new class implements \Stringable {
            public string $value = 'first';
            public int $calls = 0;

            public function __toString(): string
            {
                ++$this->calls;

                return $this->value;
            }
        };

        $attributes = new RecordingHtmlAttributes();
        $attributes->setIfExists('title', $value)->set('hidden', condition: $value);

        $this->assertSame(2, $value->calls);

        $value->value = '';
        $attributes->set('skipped', $value, $value);
        $this->assertSame(3, $value->calls);

        $this->assertSame(' title="first" hidden', (string) $attributes);

        $attributes->set('id', 'next');
        $this->assertSame(' title="first" hidden id="next"', (string) $attributes);
        $this->assertSame(3, $value->calls);
    }

    public function testSnapshotsMergeInputsWithoutLosingDuplicateKeysOrRemovals(): void
    {
        $value = new class implements \Stringable {
            public string $value = 'first';

            public function __toString(): string
            {
                return $this->value;
            }
        };

        $source = new HtmlAttributes('=odd="value" title="old"');
        $input = ['data-value' => &$value];

        $attributes = new RecordingHtmlAttributes($source);
        $attributes->mergeWith($input)->mergeWith((
            static function () use ($value) {
                yield 'class' => 'foo';
                yield 'class' => 'bar';
                yield 'title' => false;
                yield 'data-other' => $value;
            }
        )());

        $source->set('title', 'changed');
        $value->value = 'changed';
        $input['data-value'] = 'replaced';

        $this->assertSame(' =odd="value" data-value="first" class="foo bar" data-other="first"', (string) $attributes);
    }

    public function testCachesReadsAndInvalidatesAfterMutations(): void
    {
        $attributes = new class(['id' => 'first']) extends RecordingHtmlAttributes {
            public int $replays = 0;

            protected function replay(HtmlAttributes $attributes): array
            {
                ++$this->replays;

                return parent::replay($attributes);
            }
        };

        $this->assertSame(0, $attributes->replays);
        $this->assertSame('first', $attributes['id']);

        $first = $attributes->getIterator();

        iterator_to_array($attributes);
        json_encode($attributes);

        $attributes->toString();
        $this->assertSame(1, $attributes->replays);

        $attributes->set('id', 'second');
        $this->assertSame(1, $attributes->replays);
        $this->assertSame('second', $attributes['id']);
        $this->assertSame(2, $attributes->replays);
        $this->assertSame(['id' => 'first'], iterator_to_array($first));
    }

    public function testSkipsNameValidationForSkippedMutations(): void
    {
        $attributes = new RecordingHtmlAttributes();
        $attributes->set('invalid name', condition: false);
        $attributes->setIfExists('invalid name', '');
        $attributes->mergeWith(['invalid name' => true], false);

        $this->assertSame('', (string) $attributes);
    }

    public function testValidatesNamesImmediatelyWhenSettingIfExists(): void
    {
        $attributes = new RecordingHtmlAttributes();

        $this->expectException(\InvalidArgumentException::class);

        $attributes->setIfExists('invalid name', true);
    }

    public function testSnapshotsReferencedClassAndStyleArrays(): void
    {
        $class = 'foo';
        $color = 'red';
        $classes = [&$class];
        $styles = ['color' => &$color];

        $attributes = new RecordingHtmlAttributes();
        $attributes->addClass($classes)->addStyle($styles);

        $classes[0] = 'bar';
        $styles['color'] = 'blue';
        $attributes->removeClass($classes)->removeStyle(['display']);

        $this->assertSame(' class="foo" style="color: red;"', (string) $attributes);
    }

    public function testDoesNotCacheFailedReplay(): void
    {
        $attributes = new class(['title' => 'first']) extends RecordingHtmlAttributes {
            public bool $fail = true;
            public int $replays = 0;

            protected function replay(HtmlAttributes $attributes): array
            {
                ++$this->replays;
                $return = parent::replay($attributes);

                if ($this->fail) {
                    throw new \RuntimeException('Replay failed.');
                }

                return $return;
            }
        };

        try {
            $attributes->toString();

            $this->fail('The first replay must throw.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Replay failed.', $exception->getMessage());
        }

        $attributes->fail = false;
        $this->assertSame(' title="first"', $attributes->toString());
        $this->assertSame(2, $attributes->replays);
    }

    protected function createAttributes(HtmlAttributes|iterable|string|null $attributes = null): RecordingHtmlAttributes
    {
        return new RecordingHtmlAttributes($attributes);
    }

    protected function assertSameAttributes(array $expected, HtmlAttributes $attributes, string $message = ''): void
    {
        $this->assertSame($expected, iterator_to_array($attributes), $message);
    }
}
