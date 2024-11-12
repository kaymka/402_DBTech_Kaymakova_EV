<?php

namespace Kaymka\Minesweeper\Controller;

use Kaymka\Minesweeper\Game;
use Kaymka\Minesweeper\Database;

function startGame($width, $height, $mines, $saveToDatabase = false, $gameId = null, $playerName = null)
{
    $db = new Database();

    if ($width <= 0 || $height <= 0 || $mines < 0 || $mines >= $width * $height) {
        \cli\line("Invalid game parameters. Please ensure width, height, and mines are set correctly.");
        return;
    }

    if ($gameId) {
        $gameState = $db->loadGame($gameId);
        if ($gameState) {
            $game = new Game($gameState['width'], $gameState['height'], $gameState['mines']);
            $game->loadFromState($gameState);
            \cli\line("Loaded game with ID: $gameId");
        } else {
            \cli\line("Game with ID $gameId not found.");
            return;
        }
    } else {
        $game = new Game($width, $height, $mines);
    }

    $game->play($db, $playerName, $gameId);

    if ($saveToDatabase && !$gameId) {
        $gameId = $db->saveGame($game->getGameState(), $playerName);
    }
}
