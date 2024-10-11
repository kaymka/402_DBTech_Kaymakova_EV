<?php

namespace Kaymka\Minesweeper\View;

function showStartScreen() {
    \cli\line("Welcome to Minesweeper!");
}

function showBoard($board, $revealed) {
    $height = count($board);
    $width = count($board[0]);

    \cli\line("   " . implode(" ", range(0, $width - 1)));
    for ($y = 0; $y < $height; $y++) {
        $row = $y . " ";
        for ($x = 0; $x < $width; $x++) {
            if ($revealed[$y][$x]) {
                $row .= $board[$y][$x] === 0 ? '.' : $board[$y][$x];
            } else {
                $row .= '?';
            }
            $row .= " ";
        }
        \cli\line($row);
    }
}
