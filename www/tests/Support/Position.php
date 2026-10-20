<?php

declare(strict_types=1);

namespace TicTacToe\Tests\Support;

final class Position
{
    public static function winner(array $board): ?string
    {
        $flat = array_merge(...$board);
        foreach (['012', '345', '678', '036', '147', '258', '048', '246'] as $line) {
            $a = (int) $line[0];
            $b = (int) $line[1];
            $c = (int) $line[2];
            if ($flat[$a] !== null && $flat[$a] === $flat[$b] && $flat[$a] === $flat[$c]) {
                return $flat[$a];
            }
        }
        return in_array(null, $flat, true) ? null : 'draw';
    }
}
