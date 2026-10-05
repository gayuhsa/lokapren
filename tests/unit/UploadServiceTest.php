<?php

declare(strict_types=1);

use App\Services\UploadService;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * UploadService::store()'s parameter type must name a real class or interface.
 *
 * The service used to hint `CodeIgniter\HTTP\UploadedFileInterface`, a name
 * that exists in no CI4 release — so every image upload died with a TypeError
 * at the first argument, after the multipart body had already been accepted.
 * A wrong-but-loadable `use` statement is invisible to linters; it only shows
 * up when the type is checked at call time, which is what this test does.
 *
 * @internal
 */
final class UploadServiceTest extends CIUnitTestCase
{
    public function testStoreHintsAnExistingInterface(): void
    {
        $type = (new ReflectionMethod(UploadService::class, 'store'))->getParameters()[0]->getType();

        $this->assertInstanceOf(ReflectionNamedType::class, $type);

        $name = $type->getName();

        $this->assertTrue(
            interface_exists($name) || class_exists($name),
            sprintf('UploadService::store() hints "%s", which does not exist', $name)
        );

        $this->assertTrue(
            is_a(UploadedFile::class, $name, true),
            sprintf('UploadService::store() must accept %s, the object getFile() returns', UploadedFile::class)
        );
    }
}
