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

use TicTacToe\Board\Board;

class ResultChecker
{
    public const DRAW = 'draw';

    /**
     * Check three vectors (rows, columns, primary diagonal, secondary diagonal)
     *
     * @param array       $boardArray
     * @param string|null $winner
     *
     * @return array|null
     */
    function getWinner(array $boardArray = [], ?string &$winner = null)
    {
        // Checking Rows
        $matchesPD = 0;
        $matchesSD = 0;
        $totalColMatches = 0;

        $rowCoordinates = [];
        $colCoordinates = [];
        $pdCoordinates = [];
        $sdCoordinates = [];

        for ($row = 0; $row < Board::ROW_SIZE; $row++) {
            $totalRowMatches = 0;


            $firstRowCell = $boardArray[$row][0] ?? null;
            $firstCellPD = $boardArray[0][0] ?? null;
            $firstCellSD = $boardArray[Board::ROW_SIZE - 1][0] ?? null;

            $pdCoordinates[] = [$row, $row];
            $sdCoordinates[] = [$row, Board::ROW_SIZE - $row - 1];

            if ($firstCellPD !== null && $boardArray[$row][$row] === $firstCellPD) {
                $matchesPD++;
            }

            if ($matchesSD !== null && $boardArray[$row][Board::ROW_SIZE - $row - 1] === $firstCellSD) {
                $matchesSD++;
            }

            if ($matchesPD === Board::COLUMN_SIZE) {
                $winner = $firstCellPD;
                return $pdCoordinates;
            }

            if ($matchesSD === Board::COLUMN_SIZE) {
                $winner = $firstCellSD;
                return $sdCoordinates;
            }

            $firstColCell = null;
            for ($col = 0; $col < Board::COLUMN_SIZE; $col++) {
                $firstColCell = $boardArray[$col + 1 === Board::COLUMN_SIZE ? $col - 1 : $col + 1][$row] ?? null;

                $rowCoordinates[] = [$row, $col];
                $colCoordinates[] = [$col, $row];

                if ($firstRowCell !== null && $boardArray[$row][$col] === $firstRowCell) {
                    $totalRowMatches++;
                }

                if ($firstColCell !== null && $boardArray[$col][$row] === $firstColCell) {
                    $totalColMatches++;
                }
            }

            if ($totalRowMatches === Board::ROW_SIZE) {
                $winner = $firstRowCell;
                return $rowCoordinates;
            } else {
                $rowCoordinates = [];
            }

            if ($totalColMatches === Board::COLUMN_SIZE) {
                $winner = $firstColCell;
                return $colCoordinates;
            } else {
                $colCoordinates = [];
            }

            $totalColMatches = 0;
        }

        if ($this->isGameOver($boardArray)) {
            $winner = self::DRAW;
        }
        return null;
    }

    /**
     * Check base matrix if all cell entered
     *
     * @param array $boardArray
     *
     * @return bool
     */
    public function isGameOver(array $boardArray = []): bool
    {
        $totalEntered = 0;
        if (!empty($boardArray)) {
            foreach ($boardArray as $rowIndex => $row) {
                if (empty($row)) {
                    continue;
                }
                foreach ($row as $colIndex => $cell) {
                    if (!empty($cell)) {
                        $totalEntered++;
                    }
                }
            }
        }
        if ((Board::ROW_SIZE * Board::COLUMN_SIZE) === $totalEntered) {
            return true;
        }
        return false;
    }
}
