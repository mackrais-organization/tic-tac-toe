<?php

declare(strict_types=1);

namespace TicTacToe\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TicTacToe\Game\ResultChecker;

final class ResultCheckerTest extends TestCase
{
    private ResultChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new ResultChecker();
    }

    protected function tearDown(): void
    {
        unset($this->checker);
        parent::tearDown();
    }

    #[Test]
    #[DataProvider('winningLines')]
    public function findsEveryWinningLine(array $coordinates, string $symbol): void
    {
        $board = array_fill(0, 3, array_fill(0, 3, null));
        foreach ($coordinates as [$row, $column]) {
            $board[$row][$column] = $symbol;
        }
        $winner = null;
        $this->assertSame($coordinates, $this->checker->getWinner($board, $winner));
        $this->assertSame($symbol, $winner);
    }

    public static function winningLines(): iterable
    {
        foreach (['X', 'O'] as $symbol) {
            foreach (
                [
                [[0, 0], [0, 1], [0, 2]], [[1, 0], [1, 1], [1, 2]], [[2, 0], [2, 1], [2, 2]],
                [[0, 0], [1, 0], [2, 0]], [[0, 1], [1, 1], [2, 1]], [[0, 2], [1, 2], [2, 2]],
                [[0, 0], [1, 1], [2, 2]], [[0, 2], [1, 1], [2, 0]],
                ] as $line
            ) {
                yield [$line, $symbol];
            }
        }
    }

    #[Test]
    public function distinguishesDrawFromLastMoveWin(): void
    {
        $draw = [['X', 'O', 'X'], ['X', 'O', 'O'], ['O', 'X', 'X']];
        $winner = null;
        $this->assertTrue($this->checker->isGameOver($draw));
        $this->assertNull($this->checker->getWinner($draw, $winner));
        $this->assertSame('draw', $winner);
        $draw[2][0] = 'X';
        $this->checker->getWinner($draw, $winner);
        $this->assertSame('X', $winner);
    }

    #[Test]
    public function resetsPreviousWinnerAndDoesNotWinWithEmptyDiagonal(): void
    {
        $board = [['X', 'O', null], ['O', null, 'X'], [null, 'X', 'O']];
        $winner = 'X';
        $this->assertNull($this->checker->getWinner($board, $winner));
        $this->assertNull($winner);
        $this->assertFalse($this->checker->isGameOver($board));
        $this->assertFalse($this->checker->isGameOver());
    }
}
