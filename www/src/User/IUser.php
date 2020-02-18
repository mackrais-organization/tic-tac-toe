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

namespace TicTacToe\User;

use TicTacToe\Board\Board;

interface IUser
{
    /**
     * Return user name or bot name
     *
     * @return string|null
     */
    public function getUserName(): ?string;

    /**
     * Return one of allowed symbol
     * @return string|null
     * @see Board::ALLOWED_SYMBOLS
     *
     */
    public function getSymbol(): ?string;
}
