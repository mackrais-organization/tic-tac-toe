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

use TicTacToe\Board\Board;
use TicTacToe\Game\ResultChecker;

/**
 * This bot uses the Minimax Algorithm to decide its next move.
 * @see https://developercoding.com/AI/minimax.php
 * @see https://thimbleby.gitlab.io/algorithm-wiki-site/wiki/minimax_search_with_alpha-beta_pruning/
 * @see https://www.geeksforgeeks.org/minimax-algorithm-in-game-theory-set-1-introduction/
 * @see https://en.wikipedia.org/wiki/Minimax
 */
class MiniMaxBot implements IUser
{
    public const LEVEL_EASY = 'easy';
    public const LEVEL_HARD = 'hard';

    public const LEVELS = [
        self::LEVEL_EASY => ['min' => 1, 'max' => 1],
        self::LEVEL_HARD => ['min' => PHP_INT_MIN, 'max' => PHP_INT_MAX],
    ];

    protected ResultChecker $resultChecker;
    protected ?User $user;

    public function __construct()
    {
        $this->resultChecker = new ResultChecker();
        $this->user = (new User())->get();
    }

    protected function getMinScore(): int {
        return (int)static::LEVELS[$this->user->getLevel()]['min'] ?? 1;
    }
    
    protected function getMaxScore(): int {
        return (int)static::LEVELS[$this->user->getLevel()]['max'] ?? 1;
    }

    public function makeMove(Board $board)
    {
        $boardArray = $board->getBoard();
        $chosenMove = $this->findBestMove($boardArray);

        if (!empty($chosenMove)) {
            $rowIndex = current($chosenMove);
            $columnIndex = end($chosenMove);
            $board->validate($rowIndex, $columnIndex);
            $boardArray[$rowIndex][$columnIndex] = $this->getSymbol();
            $board->set($boardArray);
        }
    }

    private function findBestMove(array $boardArray): ?array
    {
        $bestScore = null;
        $bestMove = null;
        $board = $boardArray;

        $botSymbol = $this->getSymbol();
        $userSymbol = $this->user->getSymbol();

        foreach ($board as $rowIndex => $row) {
            foreach ($row as $colIndex => $col) {
                if (!$col) {
                    // Make the move
                    $board[$rowIndex][$colIndex] = $botSymbol;

                    $nextMoveScore = $this->calculate(
                        $board,
                        0,
                        false,
                        $botSymbol,
                        $userSymbol,
                         $this->getMinScore(),
                         $this->getMaxScore()
                    );
                    if ($bestScore === null || $nextMoveScore > $bestScore) {
                        $bestScore = $nextMoveScore;
                        $bestMove = [$rowIndex, $colIndex];
                        if ($bestScore ==  $this->getMaxScore()) {
                            break 2;
                        }
                    }
                    // Undo the move
                    $board[$rowIndex][$colIndex] = null;
                }
            }
        }

        return $bestMove;
    }

    private function calculate(
        array $board,
        int $depth,
        bool $isMaximizingPlayer,
        string $maximizingPlayer,
        string $minimizingPlayer,
        int $alpha,
        int $beta
    ): int {
        $winner = null;
        $this->resultChecker->getWinner($board, $winner);
        if ($winner == $maximizingPlayer) {
            return  $this->getMaxScore() - $depth;
        }
        if ($winner == $minimizingPlayer) {
            return  $this->getMinScore() + $depth;
        }
        if ($winner == ResultChecker::DRAW) {
            return 0;
        }

        $bestScore =  $this->getMaxScore();
        if ($isMaximizingPlayer) {
            $bestScore =  $this->getMinScore();
        }

        foreach ($board as $rowIndex => $row) {
            foreach ($row as $colIndex => $col) {
                if ($col) {
                    continue;
                }

                // Make the move
                $board[$rowIndex][$colIndex] = $isMaximizingPlayer ? $maximizingPlayer : $minimizingPlayer;

                $nextMoveScore = $this->calculate(
                    $board,
                    $depth + 1,
                    !$isMaximizingPlayer,
                    $maximizingPlayer,
                    $minimizingPlayer,
                    $alpha,
                    $beta
                );

                // Undo the move
                $board[$rowIndex][$colIndex] = null;

                if ($isMaximizingPlayer) {
                    $bestScore = max($bestScore, $nextMoveScore);
                    $alpha = max($alpha, $bestScore);
                    if ($beta <= $alpha) {
                        break 2;
                    }
                } else {
                    $bestScore = min($bestScore, $nextMoveScore);
                    $beta = min($beta, $bestScore);
                    if ($beta <= $alpha) {
                        break 2;
                    }
                }
            }
        }

        return $bestScore;
    }

    public function getSymbol(): string
    {
        return $this->user instanceof User && $this->user->getSymbol() === Board::ALLOWED_SYMBOLS[0]
            ? Board::ALLOWED_SYMBOLS[1]
            : Board::ALLOWED_SYMBOLS[0];
    }

    public function getUserName(): string
    {
        return 'MackRais MiniMax Bot v1.0.5';
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
