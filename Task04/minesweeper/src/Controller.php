<?php

namespace Kaymka\Minesweeper\Controller;

use Kaymka\Minesweeper\Game;
use Kaymka\Minesweeper\Database;

function startGame($width, $height, $mines, $saveToDatabase = false, $gameId = null, $playerName = null)
{
    $db = new Database();

    // Проверка параметров
    if ($width <= 0 || $height <= 0 || $mines < 0 || $mines >= $width * $height) {
        \cli\line("Invalid game parameters. Please ensure width, height, and mines are set correctly.");
        return;
    }

    // Если передан gameId, загружаем игру из базы данных
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
        // Если gameId не передан, создаем новую игру
        $game = new Game($width, $height, $mines);
    }

    // Начало игрового процесса
    $game->play($db, $playerName, $gameId);

    // Сохраняем игру, если указан флаг сохранения
    if ($saveToDatabase && !$gameId) {
        $gameId = $db->saveGame($game->getGameState(), $playerName);
    }
}
