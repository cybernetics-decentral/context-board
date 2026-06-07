<?php

/** @noinspection PhpUnhandledExceptionInspection */

namespace Tests\Unit;

use Tests\TestCase;

class FlatfileStoreTest extends TestCase
{
    private \FlatfileStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = new \FlatfileStore($this->tempDir);
    }

    // UT-1.1.1: readJson on non-existent file
    public function testReadJsonOnNonExistentFileReturnsEmptyArray(): void
    {
        $result = $this->store->readJson('nonexistent.json');
        $this->assertSame([], $result);
    }

    // UT-1.1.2: writeJson then readJson roundtrip
    public function testWriteJsonThenReadJsonRoundtrip(): void
    {
        $data = ['foo' => 'bar', 'num' => 42];
        $this->store->writeJson('test.json', $data);
        $result = $this->store->readJson('test.json');
        $this->assertSame($data, $result);
    }

    // UT-1.1.3: writeJson with pretty print
    public function testWriteJsonUsesPrettyPrint(): void
    {
        $this->store->writeJson('test.json', ['a' => 'b']);
        $raw = file_get_contents($this->tempDir . '/test.json');
        $this->assertStringContainsString("    ", $raw); // indentation exists
    }

    // UT-1.1.5: exists on existing file
    public function testExistsReturnsTrueForExistingFile(): void
    {
        file_put_contents($this->tempDir . '/exists.txt', 'hello');
        $this->assertTrue($this->store->exists('exists.txt'));
    }

    // UT-1.1.6: exists on missing file
    public function testExistsReturnsFalseForMissingFile(): void
    {
        $this->assertFalse($this->store->exists('missing.txt'));
    }

    // UT-1.1.7: delete removes file
    public function testDeleteRemovesFile(): void
    {
        file_put_contents($this->tempDir . '/todelete.txt', 'bye');
        $this->store->delete('todelete.txt');
        $this->assertFalse(file_exists($this->tempDir . '/todelete.txt'));
    }

    // UT-1.1.8: deleteDirectory recursively removes
    public function testDeleteDirectoryRecursivelyRemoves(): void
    {
        mkdir($this->tempDir . '/sub/dir', 0755, true);
        file_put_contents($this->tempDir . '/sub/dir/a.txt', 'a');
        $this->store->deleteDirectory('sub');
        $this->assertFalse(is_dir($this->tempDir . '/sub'));
    }

    // UT-1.1.9: createDirectory makes directories
    public function testCreateDirectoryMakesDirectories(): void
    {
        $this->store->createDirectory('nested/path/dir');
        $this->assertTrue(is_dir($this->tempDir . '/nested/path/dir'));
    }

    // UT-1.1.10: readJson on corrupt JSON
    public function testReadJsonOnCorruptJsonReturnsEmptyArray(): void
    {
        file_put_contents($this->tempDir . '/bad.json', '{invalid json!!!');
        $result = $this->store->readJson('bad.json');
        $this->assertSame([], $result);
    }

    // UT-1.1.12: listDirectory returns files
    public function testListDirectoryReturnsFiles(): void
    {
        $subDir = $this->tempDir . '/listtest';
        mkdir($subDir, 0755, true);
        file_put_contents($subDir . '/a.json', '{}');
        file_put_contents($subDir . '/b.json', '{}');
        file_put_contents($subDir . '/c.json', '{}');
        $store = new \FlatfileStore($subDir);
        $files = $store->listDirectory('.');
        $this->assertCount(3, $files);
    }

    // UT-1.1.13: readRaw and writeRaw roundtrip
    public function testReadRawAndWriteRawRoundtrip(): void
    {
        $content = "Hello\nWorld!";
        $this->store->writeRaw('raw.txt', $content);
        $result = $this->store->readRaw('raw.txt');
        $this->assertSame($content, $result);
    }

    // UT-1.1.14: writeJson with Unicode/emoji
    public function testWriteJsonPreservesUnicodeAndEmoji(): void
    {
        $data = ['msg' => 'Hello 🌍 café'];
        $this->store->writeJson('unicode.json', $data);
        $result = $this->store->readJson('unicode.json');
        $this->assertSame('Hello 🌍 café', $result['msg']);
    }

    // UTF-8 multi-byte characters
    public function testWriteJsonPreservesUtf8MultiByte(): void
    {
        $data = ['greeting' => 'こんにちは'];
        $this->store->writeJson('utf8.json', $data);
        $result = $this->store->readJson('utf8.json');
        $this->assertSame('こんにちは', $result['greeting']);
    }

    // Read non-existent for readRaw throws exception
    public function testReadRawOnMissingFileThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->store->readRaw('missing.raw');
    }

    // Delete non-existent file does not throw
    public function testDeleteNonExistentFileDoesNotThrow(): void
    {
        $this->store->delete('does_not_exist.txt');
        $this->assertTrue(true); // no exception
    }
}
