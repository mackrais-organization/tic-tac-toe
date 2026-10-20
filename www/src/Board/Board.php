<?php

/**
 * Created by PhpStorm.
 * PHP Version: 7.4
 *
 * @category
 * @author     Oleh Boiko <developer@mackais.com>
 * @copyright  2018-2020 @MackRais
 * @link       <https://mackrais.com>
 * @date       2020-02-17
 */

declare(strict_types=1);

namespace TicTacToe\Board;

use TicTacToe\Exception\InvalidBoardUser;
use TicTacToe\Exception\InvalidValidation;
use TicTacToe\Game\ResultChecker;
use TicTacToe\Storage\PhpSessionStorage;
use TicTacToe\User\MiniMaxBot;
use TicTacToe\User\User;

class Board
{
    public const SYMBOL_O = 'O';
    public const SYMBOL_X = 'X';
    public const ALLOWED_SYMBOLS = [self::SYMBOL_O, self::SYMBOL_X];
    public const ROW_SIZE = 3;
    public const COLUMN_SIZE = 3;

    private const string STORAGE_KEY = 'board';

    protected PhpSessionStorage $storage;
    protected ?User $user;
    protected ResultChecker $resultChecker;
    protected MiniMaxBot $miniMaxBot;

    public function __construct()
    {
        $this->storage = new PhpSessionStorage();
        $this->user = new User()->get();
        $this->resultChecker = new ResultChecker();
        $this->miniMaxBot = new MiniMaxBot();
    }

    public function getBoard()
    {
        return $this->storage->get(self::STORAGE_KEY, $this->getDefaultBoard());
    }

    public function makeMove(?int $rowIndex, ?int $columnIndex)
    {
        $this->validate($rowIndex, $columnIndex);
        $board = $this->getBoard();
        $board[$rowIndex][$columnIndex] = $this->user->getSymbol();
        $this->set($board);
        $this->miniMaxBot->makeMove($this);
    }

    public function validate(?int $rowIndex, ?int $columnIndex)
    {
        if (!$this->user instanceof User) {
            throw new InvalidBoardUser('Incorrect user.');
        }
        $board = $this->getBoard();
        $winner = null;
        $this->resultChecker->getWinner($board, $winner);
        if ($winner !== null) {
            throw new InvalidBoardUser('Game over.');
        }
        if (
            $rowIndex === null || $columnIndex === null || $rowIndex < 0 || $rowIndex >= self::ROW_SIZE
            || $columnIndex < 0 || $columnIndex >= self::COLUMN_SIZE
        ) {
            throw new InvalidValidation('Incorrect coordinates');
        }
        if ($board[$rowIndex][$columnIndex] !== null) {
            throw new InvalidValidation('Coordinates already taken');
        }
    }

    public function set(array $board)
    {
        $this->storage->set(self::STORAGE_KEY, $board);
    }

    public function clear()
    {
        if ($this->storage->has(self::STORAGE_KEY)) {
            $this->storage->delete(self::STORAGE_KEY);
        }
    }

    public static function isValidSymbol(string $symbol): bool
    {
        return in_array($symbol, self::ALLOWED_SYMBOLS);
    }

    protected function getDefaultBoard()
    {
        $board = [];
        for ($rowIndex = 0; $rowIndex < static::ROW_SIZE; $rowIndex++) {
            for ($columnIndex = 0; $columnIndex < static::COLUMN_SIZE; $columnIndex++) {
                $board[$rowIndex][$columnIndex] = null;
            }
        }
        return $board;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->miniMaxBot->setUser($user);
    }
}
