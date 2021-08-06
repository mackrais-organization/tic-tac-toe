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
use TicTacToe\Exception\InvalidValidation;
use TicTacToe\Storage\PhpSessionStorage;

class User implements IUser
{
    private const STORAGE_KEY = 'user';

    protected string $userName;

    protected string $symbol;

    protected string $level;

    protected PhpSessionStorage $storage;

    public function __construct()
    {
        $this->storage = new PhpSessionStorage();
    }

    public function create(string $userName, string $symbol, string $level)
    {
        if (!$this->storage->has(self::STORAGE_KEY)) {
            $this->userName = $userName;
            $this->symbol = $symbol;
            $this->level = $level;
            $this->validate();
            $this->storage->set(self::STORAGE_KEY, serialize($this));
        }
    }

    public function get(): ?self
    {
        $user = $this->storage->get(self::STORAGE_KEY);
        return $this->storage->has(self::STORAGE_KEY) ? unserialize($user) : null;
    }

    public function logout()
    {
        if ($this->storage->has(self::STORAGE_KEY)) {
            $this->storage->delete(self::STORAGE_KEY);
        }
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function validate()
    {
        if (empty($this->userName)) {
            throw new InvalidValidation('Username can not be blank');
        }
        if (empty($this->symbol)) {
            throw new InvalidValidation('Symbol can not be blank');
        }
        if (!Board::isValidSymbol($this->symbol)) {
            throw new InvalidValidation(
                sprintf('Please use one of the following symbols: "%s".', implode('", "', Board::ALLOWED_SYMBOLS))
            );
        }
        $level = MiniMaxBot::LEVELS[$this->level] ?? null;
        if($level === null){
            throw new InvalidValidation(
                sprintf('Please use one of the following level: "%s".', implode('", "', array_keys(MiniMaxBot::LEVELS)))
            );
        }
    }

    /**
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }
}
