<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Mailer;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Mailer\InlineImageEmbedder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\File;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Mime\Email;

class InlineImageEmbedderTest extends TestCase
{
    public function testDoesNothingWithoutAnHtmlBody(): void
    {
        $email = new Email()->text('');

        $this->getInlineImageEmbedder()->embedImages($email);

        $this->assertCount(0, $email->getAttachments());
        $this->assertNull($email->getHtmlBody());
    }

    public function testDoesPreserveTheHtmlCharset(): void
    {
        $email = new Email()->html('<html lang="en"></html>', 'iso-8859-1');

        $this->getInlineImageEmbedder()->embedImages($email);

        $this->assertSame('iso-8859-1', $email->getHtmlCharset());
    }

    public function testDoesEmbedRelativeImages(): void
    {
        $email = new Email()->html('<p><img src="images/dummy.jpg"></p>');

        $this->getInlineImageEmbedder()->embedImages($email);

        $this->assertStringContainsString('src="cid:images/dummy.jpg"', $email->getHtmlBody());
        $this->assertCount(1, $email->getAttachments());
        $this->assertSame('images/dummy.jpg', $email->getAttachments()[0]->getFilename());
    }

    public function testDoesStripTheBaseUrl(): void
    {
        $email = new Email()->html('<p><img src="https://example.com/images/dummy.jpg"></p>');

        $this->getInlineImageEmbedder()->embedImages($email, 'https://example.com/');

        $this->assertStringContainsString('src="cid:images/dummy.jpg"', $email->getHtmlBody());
        $this->assertCount(1, $email->getAttachments());
        $this->assertSame('images/dummy.jpg', $email->getAttachments()[0]->getFilename());
    }

    public function testDoesDeduplicateRepeatedImages(): void
    {
        $email = new Email()->html('<p><img src="images/dummy.jpg"><img src="images/dummy.jpg"><img src="images/dummy.jpg"></p>');

        $this->getInlineImageEmbedder()->embedImages($email);

        $this->assertStringContainsString('src="cid:images/dummy.jpg"', $email->getHtmlBody());
        $this->assertSame(3, preg_match_all('/src="cid:images\/dummy\.jpg"/', $email->getHtmlBody()));
        $this->assertCount(1, $email->getAttachments());
        $this->assertSame('images/dummy.jpg', $email->getAttachments()[0]->getFilename());
    }

    public function testDoesNotEmbedExternalImages(): void
    {
        $email = new Email()->html('<p><img src="https://example.com/images/dummy.jpg"></p>');

        $this->getInlineImageEmbedder()->embedImages($email);

        $this->assertStringNotContainsString('src="cid:images/dummy.jpg"', $email->getHtmlBody());
        $this->assertCount(0, $email->getAttachments());
    }

    public function testDoesNotEmbedMissingFiles(): void
    {
        $email = new Email()->html('<p><img src="images/dummy2.jpg"></p>');

        $this->getInlineImageEmbedder()->embedImages($email);

        $this->assertStringNotContainsString('src="cid:images/dummy2.jpg"', $email->getHtmlBody());
        $this->assertCount(0, $email->getAttachments());
    }

    public function testDoesNotEmbedFilesOutsideTheImageDirectory(): void
    {
        $email = new Email()->html('<p><img src="../../../etc/passwd.jpg"></p>');

        $this->getInlineImageEmbedder()->embedImages($email);

        $this->assertStringNotContainsString('src="cid:../../../etc/passwd.jpg"', $email->getHtmlBody());
        $this->assertCount(0, $email->getAttachments());
    }

    public function testDoesNotEmbedTheImagesTwice(): void
    {
        $embedder = $this->getInlineImageEmbedder();
        $email = new Email()->html('<p><img src="images/dummy.jpg"></p>');

        $embedder->embedImages($email);
        $html = $email->getHtmlBody();
        $embedder->embedImages($email);

        $this->assertSame($html, $email->getHtmlBody());
        $this->assertCount(1, $email->getAttachments());
    }

    public function testDecodesUrlEncodedPaths(): void
    {
        $tempDir = $this->getTempDir();
        $imageDir = Path::join($tempDir, 'images');

        $filesystem = new Filesystem();
        $filesystem->mkdir($imageDir);
        $filesystem->copy(
            Path::join($this->getFixturesDir(), 'images/dummy.jpg'),
            Path::join($imageDir, 'dummy copy.jpg'),
        );

        $email = new Email()->html('<p><img src="images/dummy%20copy.jpg"></p>');

        $this->getInlineImageEmbedder(fixturesDir: $tempDir)->embedImages($email);

        $this->assertStringContainsString('src="cid:images/dummy copy.jpg"', $email->getHtmlBody());
        $this->assertCount(1, $email->getAttachments());
        $this->assertSame('images/dummy copy.jpg', $email->getAttachments()[0]->getFilename());
    }

    public function testDoesCreateDeferredImages(): void
    {
        $file = $this->createMock(File::class);
        $file
            ->method('exists')
            ->willReturn(false)
        ;

        $file
            ->expects($this->once())
            ->method('createIfDeferred')
            ->willReturn(true)
        ;

        $framework = $this->createContaoFrameworkStub([], [File::class => $file]);
        $email = new Email()->html('<p><img src="assets/images/1/deferred.jpg"></p>');

        $this->getInlineImageEmbedder($framework)->embedImages($email);

        $this->assertCount(1, $email->getAttachments());
    }

    private function getInlineImageEmbedder(ContaoFramework|null $framework = null, string|null $fixturesDir = null): InlineImageEmbedder
    {
        return new InlineImageEmbedder($framework ?? $this->createContaoFrameworkStub(), $fixturesDir ?? $this->getFixturesDir());
    }
}
