<?php

/**
 * Created by PhpStorm.
 * PHP Version: 7.4
 *
 * @category
 * @author     Oleh Boiko <developer@mackais.com>
 * @copyright  2018-2020 @MackRais
 * @link       <https://mackrais.com>
 * @date       2/17/20
 */

declare(strict_types=1);

namespace TicTacToe\User;

use Random\Randomizer;
use TicTacToe\Board\Board;
use TicTacToe\Exception\InvalidBoardUser;
use TicTacToe\Game\ResultChecker;

class MiniMaxBot implements IUser
{
    public const LEVEL_EASY = 'easy';
    public const LEVEL_NORMAL = 'normal';
    public const LEVEL_HARD = 'hard';
    public const LEVELS = [self::LEVEL_EASY, self::LEVEL_NORMAL, self::LEVEL_HARD];

    private ?User $user;
    private readonly ResultChecker $resultChecker;
    private readonly Randomizer $randomizer;

    public function __construct(?Randomizer $randomizer = null)
    {
        $this->user = new User()->get();
        $this->resultChecker = new ResultChecker();
        $this->randomizer = $randomizer ?? new Randomizer();
    }

    public function makeMove(Board $board): void
    {
        if ($this->user === null) {
            throw new InvalidBoardUser('Incorrect user.');
        }
        $cells = $board->getBoard();
        $winner = null;
        $this->resultChecker->getWinner($cells, $winner);
        if ($winner !== null) {
            return;
        }
        $moves = $this->availableMoves($cells);
        $move = match ($this->user->getLevel()) {
            self::LEVEL_HARD => $this->bestMove($cells, $moves),
            self::LEVEL_NORMAL => $this->winningMove($cells, $moves, $this->getSymbol())
                ?? $this->winningMove($cells, $moves, $this->user->getSymbol())
                ?? $moves[$this->randomizer->getInt(0, count($moves) - 1)],
            default => $moves[$this->randomizer->getInt(0, count($moves) - 1)],
        };
        [$row, $column] = $move;
        $board->validate($row, $column);
        $cells[$row][$column] = $this->getSymbol();
        $board->set($cells);
    }

    private function availableMoves(array $board): array
    {
        $moves = [];
        foreach ($board as $row => $cells) {
            foreach ($cells as $column => $cell) {
                if ($cell === null) {
                    $moves[] = [$row, $column];
                }
            }
        }
        return $moves;
    }

    private function winningMove(array $board, array $moves, string $symbol): ?array
    {
        foreach ($moves as [$row, $column]) {
            $board[$row][$column] = $symbol;
            $winner = null;
            $this->resultChecker->getWinner($board, $winner);
            if ($winner === $symbol) {
                return [$row, $column];
            }
            $board[$row][$column] = null;
        }
        return null;
    }

    private function bestMove(array $board, array $moves): array
    {
        $bestScore = -PHP_INT_MAX;
        $bestMove = $moves[0];
        foreach ($moves as [$row, $column]) {
            $board[$row][$column] = $this->getSymbol();
            $score = $this->calculate($board, 0, false, -PHP_INT_MAX, PHP_INT_MAX);
            $board[$row][$column] = null;
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove = [$row, $column];
            }
        }
        return $bestMove;
    }

    private function calculate(array $board, int $depth, bool $maximizing, int $alpha, int $beta): int
    {
        $winner = null;
        $this->resultChecker->getWinner($board, $winner);
        if ($winner !== null) {
            return $winner === ResultChecker::DRAW ? 0 : ($winner === $this->getSymbol() ? 10 - $depth : $depth - 10);
        }
        $best = $maximizing ? -PHP_INT_MAX : PHP_INT_MAX;
        foreach ($this->availableMoves($board) as [$row, $column]) {
            $board[$row][$column] = $maximizing ? $this->getSymbol() : $this->user->getSymbol();
            $score = $this->calculate($board, $depth + 1, !$maximizing, $alpha, $beta);
            $board[$row][$column] = null;
            if ($maximizing) {
                $best = max($best, $score);
                $alpha = max($alpha, $best);
            } else {
                $best = min($best, $score);
                $beta = min($beta, $best);
            }
            if ($beta <= $alpha) {
                break;
            }
        }
        return $best;
    }

    public function getSymbol(): string
    {
        return $this->user?->getSymbol() === Board::SYMBOL_O ? Board::SYMBOL_X : Board::SYMBOL_O;
    }

    public function getUserName(): string
    {
        return 'MackRais MiniMax Bot v1.0.3';
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
