<?php

declare(strict_types=1);

namespace TicTacToe\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Random\Engine\Mt19937;
use Random\Randomizer;
use TicTacToe\Board\Board;
use TicTacToe\Exception\InvalidBoardUser;
use TicTacToe\Tests\Support\Position;
use TicTacToe\Tests\Support\SessionTestCase;
use TicTacToe\User\MiniMaxBot;
use TicTacToe\User\User;

final class MiniMaxBotTest extends SessionTestCase
{
    private Board $board;
    private MiniMaxBot $bot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->start('normal', 'X');
    }

    protected function tearDown(): void
    {
        unset($this->board, $this->bot);
        parent::tearDown();
    }

    private function start(string $level, string $symbol): void
    {
        $_SESSION = [];
        new User()->create('Player', $symbol, $level);
        $this->board = new Board();
        $this->bot = new MiniMaxBot(new Randomizer(new Mt19937(42)));
    }

    #[Test]
    public function exposesVersionedDisplayName(): void
    {
        $this->assertSame('MackRais MiniMax Bot v1.0.3', $this->bot->getUserName());
    }

    #[Test]
    #[DataProvider('tacticalLevels')]
    public function takesWinBeforeBlocking(string $level, string $human): void
    {
        $this->start($level, $human);
        $bot = $this->bot->getSymbol();
        $this->board->set([[$bot, $bot, null], [$human, $human, null], [null, null, null]]);
        $this->bot->makeMove($this->board);
        $this->assertSame($bot, $this->board->getBoard()[0][2]);
        $this->assertSame($bot, Position::winner($this->board->getBoard()));
    }

    #[Test]
    #[DataProvider('tacticalLevels')]
    public function blocksImmediateLoss(string $level, string $human): void
    {
        $this->start($level, $human);
        $bot = $this->bot->getSymbol();
        $this->board->set([[$human, $human, null], [null, $bot, null], [null, null, null]]);
        $this->bot->makeMove($this->board);
        $this->assertSame($bot, $this->board->getBoard()[0][2]);
    }

    public static function tacticalLevels(): iterable
    {
        foreach (['normal', 'hard'] as $level) {
            foreach (['X', 'O'] as $symbol) {
                yield [$level, $symbol];
            }
        }
    }

    #[Test]
    public function normalCanMissAForkWhileHardAvoidsIt(): void
    {
        $position = [['X', null, null], [null, 'O', null], [null, null, 'X']];
        $foundCorner = false;
        for ($seed = 0; $seed < 20; ++$seed) {
            $this->board->set($position);
            $bot = new MiniMaxBot(new Randomizer(new Mt19937($seed)));
            $bot->makeMove($this->board);
            $cells = $this->board->getBoard();
            $foundCorner = $foundCorner || $cells[0][2] === 'O' || $cells[2][0] === 'O';
        }
        $this->assertTrue($foundCorner, 'Normal should remain beatable by a fork');
        $this->start('hard', 'X');
        $this->board->set($position);
        $this->bot->makeMove($this->board);
        $cells = $this->board->getBoard();
        $this->assertNull($cells[0][2]);
        $this->assertNull($cells[2][0]);
    }

    #[Test]
    public function easyCanMissAnImmediateThreatButOnlyUsesFreeCells(): void
    {
        $this->start('easy', 'X');
        $this->bot = new MiniMaxBot(new Randomizer(new Mt19937(0)));
        $before = [['X', 'X', null], [null, 'O', null], [null, null, null]];
        $this->board->set($before);
        $this->bot->makeMove($this->board);
        $after = $this->board->getBoard();
        $this->assertNull($after[0][2]);
        $changes = 0;
        foreach ($before as $row => $cells) {
            foreach ($cells as $column => $cell) {
                if ($after[$row][$column] !== $cell) {
                    ++$changes;
                    $this->assertNull($cell);
                    $this->assertSame('O', $after[$row][$column]);
                }
            }
        }
        $this->assertSame(1, $changes);
    }

    #[Test]
    public function doesNotPlayOnFinishedBoard(): void
    {
        foreach (
            [
            [['X', 'X', 'X'], ['O', 'O', null], [null, null, null]],
            [['X', 'O', 'X'], ['X', 'O', 'O'], ['O', 'X', 'X']],
            ] as $finished
        ) {
            $this->board->set($finished);
            $this->bot->makeMove($this->board);
            $this->assertSame($finished, $this->board->getBoard());
        }
    }

    #[Test]
    public function requiresPlayer(): void
    {
        new User()->logout();
        $bot = new MiniMaxBot();
        $this->expectException(InvalidBoardUser::class);
        $bot->makeMove($this->board);
    }

    #[Test]
    #[Group('exhaustive')]
    public function hardNeverLosesAgainstAnyHumanContinuation(): void
    {
        foreach (['X', 'O'] as $human) {
            $this->start('hard', $human);
            $visited = [];
            $this->explore(array_fill(0, 3, array_fill(0, 3, null)), 'X', $human, $visited);
            $this->assertGreaterThan(100, count($visited));
        }
    }

    private function explore(array $cells, string $turn, string $human, array &$visited): void
    {
        $key = json_encode($cells, JSON_THROW_ON_ERROR);
        if (isset($visited[$key])) {
            return;
        }
        $visited[$key] = true;
        $winner = Position::winner($cells);
        $this->assertNotSame($human, $winner, $key);
        if ($winner !== null) {
            return;
        }
        if ($turn !== $human) {
            $this->board->set($cells);
            $this->bot->makeMove($this->board);
            $this->explore($this->board->getBoard(), $human, $human, $visited);
            return;
        }
        foreach ($cells as $row => $columns) {
            foreach ($columns as $column => $cell) {
                if ($cell === null) {
                    $next = $cells;
                    $next[$row][$column] = $human;
                    $this->explore($next, $this->bot->getSymbol(), $human, $visited);
                }
            }
        }
    }
}
