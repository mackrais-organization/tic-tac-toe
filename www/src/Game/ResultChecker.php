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

namespace TicTacToe\Game;

class ResultChecker
{
    public const DRAW = 'draw';

    public function getWinner(array $boardArray = [], ?string &$winner = null): ?array
    {
        $winner = null;
        $lines = [
            [[0, 0], [0, 1], [0, 2]], [[1, 0], [1, 1], [1, 2]], [[2, 0], [2, 1], [2, 2]],
            [[0, 0], [1, 0], [2, 0]], [[0, 1], [1, 1], [2, 1]], [[0, 2], [1, 2], [2, 2]],
            [[0, 0], [1, 1], [2, 2]], [[0, 2], [1, 1], [2, 0]],
        ];
        foreach ($lines as $line) {
            [[$r1, $c1], [$r2, $c2], [$r3, $c3]] = $line;
            $symbol = $boardArray[$r1][$c1] ?? null;
            if ($symbol !== null && $symbol === ($boardArray[$r2][$c2] ?? null) && $symbol === ($boardArray[$r3][$c3] ?? null)) {
                $winner = $symbol;
                return $line;
            }
        }
        if ($this->isGameOver($boardArray)) {
            $winner = self::DRAW;
        }
        return null;
    }

    public function isGameOver(array $boardArray = []): bool
    {
        for ($row = 0; $row < 3; ++$row) {
            for ($column = 0; $column < 3; ++$column) {
                if (($boardArray[$row][$column] ?? null) === null) {
                    return false;
                }
            }
        }
        return true;
    }
}
