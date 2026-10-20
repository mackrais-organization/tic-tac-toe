<?php

declare(strict_types=1);

namespace TicTacToe\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TicTacToe\Exception\InvalidValidation;
use TicTacToe\Tests\Support\SessionTestCase;
use TicTacToe\User\User;

final class UserTest extends SessionTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User();
    }

    protected function tearDown(): void
    {
        unset($this->user);
        parent::tearDown();
    }

    #[Test]
    public function persistsUserAndDoesNotReplaceActiveGame(): void
    {
        $this->assertNull($this->user->get());
        $this->user->create('Player', 'O', 'normal');
        $this->user->create('Other', 'X', 'hard');
        $saved = new User()->get();
        $this->assertSame('Player', $saved->getUserName());
        $this->assertSame('O', $saved->getSymbol());
        $this->assertSame('normal', $saved->getLevel());
        $this->user->logout();
        $this->assertNull($this->user->get());
        $this->user->logout();
    }

    #[Test]
    #[DataProvider('invalidUsers')]
    public function rejectsInvalidUser(string $name, string $symbol, string $level): void
    {
        $this->expectException(InvalidValidation::class);
        $this->user->create($name, $symbol, $level);
    }

    public static function invalidUsers(): iterable
    {
        yield ['', 'X', 'easy'];
        yield ['Player', '', 'easy'];
        yield ['Player', 'Z', 'easy'];
        yield ['Player', 'X', 'unknown'];
    }
}
