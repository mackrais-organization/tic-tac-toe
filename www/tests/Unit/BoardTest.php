<?php

declare(strict_types=1);

namespace TicTacToe\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TicTacToe\Board\Board;
use TicTacToe\Exception\InvalidBoardUser;
use TicTacToe\Exception\InvalidValidation;
use TicTacToe\Tests\Support\SessionTestCase;
use TicTacToe\User\User;

final class BoardTest extends SessionTestCase
{
    private Board $board;

    protected function setUp(): void
    {
        parent::setUp();
        new User()->create('Player', 'X', 'hard');
        $this->board = new Board();
    }

    protected function tearDown(): void
    {
        unset($this->board);
        parent::tearDown();
    }

    #[Test]
    public function alternatesLocalPlayersAndUndoesOneMove(): void
    {
        $this->board->startMatch('duo');
        $id = $this->board->getRoundId();
        $this->assertNotNull($id);
        $this->assertSame('duo', $this->board->getMode());
        $this->assertFalse($this->board->canUndo());
        $this->board->makeMove(0, 0);
        $this->assertSame('O', $this->board->getTurn());
        $this->assertSame([['X', null, null], [null, null, null], [null, null, null]], $this->board->getBoard());
        $this->board->makeMove(1, 1);
        $this->assertSame('X', $this->board->getTurn());
        $this->assertSame('O', $this->board->getBoard()[1][1]);
        $this->board->undo();
        $this->assertSame('O', $this->board->getTurn());
        $this->assertNull($this->board->getBoard()[1][1]);
        $this->assertTrue($this->board->canUndo());
        $this->board->undo();
        $this->assertFalse($this->board->canUndo());
        $this->board->startMatch('solo');
        $this->assertNotSame($id, $this->board->getRoundId());
        $this->assertSame('solo', $this->board->getMode());
    }

    #[Test]
    public function undoesHumanAndBotTogether(): void
    {
        $before = $this->board->getBoard();
        $this->board->makeMove(0, 0);
        $this->assertTrue($this->board->canUndo());
        $this->board->undo();
        $this->assertSame($before, $this->board->getBoard());
        $this->expectException(InvalidValidation::class);
        $this->board->undo();
    }

    #[Test]
    public function rejectsUndoAfterScoredResult(): void
    {
        $this->board->startMatch('duo');
        foreach ([[0, 0], [1, 0], [0, 1], [1, 1], [0, 2]] as [$row, $column]) {
            $this->board->makeMove($row, $column);
        }
        $this->assertFalse($this->board->canUndo());
        $this->expectException(InvalidValidation::class);
        $this->board->undo();
    }

    #[Test]
    public function rejectsInvalidModeWithoutClearingMatch(): void
    {
        $this->board->makeMove(0, 0);
        $before = $this->board->getBoard();
        try {
            $this->board->startMatch('invalid');
            $this->fail('Invalid mode was accepted');
        } catch (InvalidValidation) {
            $this->assertSame($before, $this->board->getBoard());
        }
    }

    #[Test]
    public function playsBothTurnsAndClearsBoard(): void
    {
        $this->board->makeMove(0, 0);
        $cells = array_merge(...$this->board->getBoard());
        $this->assertSame('X', $cells[0]);
        $this->assertSame(1, count(array_filter($cells, fn ($cell) => $cell === 'O')));
        $this->board->clear();
        $this->assertSame(array_fill(0, 3, array_fill(0, 3, null)), $this->board->getBoard());
        $this->board->clear();
    }

    #[Test]
    #[DataProvider('invalidCoordinates')]
    public function rejectsOutOfBoundsWithoutChangingBoard(?int $row, ?int $column): void
    {
        $before = $this->board->getBoard();
        try {
            $this->board->makeMove($row, $column);
            $this->fail('Invalid coordinates were accepted');
        } catch (InvalidValidation $exception) {
            $this->assertSame('Incorrect coordinates', $exception->getMessage());
            $this->assertSame($before, $this->board->getBoard());
        }
    }

    public static function invalidCoordinates(): iterable
    {
        yield [-1, 0];
        yield [3, 0];
        yield [0, -1];
        yield [0, 3];
        yield [null, 0];
        yield [0, null];
        yield [999, 999];
    }

    #[Test]
    public function rejectsOccupiedCell(): void
    {
        $this->board->set([['X', null, null], [null, null, null], [null, null, null]]);
        $this->expectException(InvalidValidation::class);
        $this->expectExceptionMessage('Coordinates already taken');
        $this->board->makeMove(0, 0);
    }

    #[Test]
    public function stopsAfterHumanWins(): void
    {
        $this->board->set([['X', 'X', null], ['O', 'O', null], [null, null, null]]);
        $this->board->makeMove(0, 2);
        $this->assertSame([['X', 'X', 'X'], ['O', 'O', null], [null, null, null]], $this->board->getBoard());
        $this->expectException(InvalidBoardUser::class);
        $this->expectExceptionMessage('Game over.');
        $this->board->makeMove(2, 2);
    }

    #[Test]
    public function requiresUser(): void
    {
        new User()->logout();
        $board = new Board();
        $this->expectException(InvalidBoardUser::class);
        $board->makeMove(0, 0);
    }

    #[Test]
    public function updatesBotWhenUserIsAssignedAfterConstruction(): void
    {
        new User()->logout();
        $board = new Board();
        $user = new User();
        $user->create('New player', 'O', 'hard');
        $board->setUser($user);
        $board->set([['X', null, null], [null, null, null], [null, null, null]]);
        $board->makeMove(1, 1);
        $cells = array_merge(...$board->getBoard());
        $this->assertSame('O', $cells[4]);
        $this->assertSame(2, count(array_filter($cells, fn ($cell) => $cell === 'X')));
    }
}
