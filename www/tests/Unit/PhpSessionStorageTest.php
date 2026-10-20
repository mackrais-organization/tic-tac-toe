<?php

declare(strict_types=1);

namespace TicTacToe\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use TicTacToe\Storage\PhpSessionStorage;
use TicTacToe\Tests\Support\SessionTestCase;

final class PhpSessionStorageTest extends SessionTestCase
{
    private PhpSessionStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = new PhpSessionStorage();
    }

    protected function tearDown(): void
    {
        unset($this->storage);
        parent::tearDown();
    }

    #[Test]
    public function storesNullSeparatelyFromMissingKeys(): void
    {
        $this->assertFalse($this->storage->has('key'));
        $this->assertSame('fallback', $this->storage->get('key', 'fallback'));
        $this->assertSame($this->storage, $this->storage->set('key', null));
        $this->assertTrue($this->storage->has('key'));
        $this->assertNull($this->storage->get('key', 'fallback'));
        $this->storage->set('other', ['X']);
        $this->assertSame(['X'], new PhpSessionStorage()->get('other'));
        $this->assertSame($this->storage, $this->storage->delete('key'));
        $this->assertFalse($this->storage->has('key'));
        $this->assertSame(['X'], $this->storage->get('other'));
        $this->storage->delete('missing');
    }
}
