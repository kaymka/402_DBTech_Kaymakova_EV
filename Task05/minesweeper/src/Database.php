<?php

namespace Kaymka\Minesweeper;

use RedBeanPHP\R as R;

//class_alias('\RedBeanPHP\R', 'R');

class Database
{
    public function __construct()
    {
        // Проверка на существование подключения
        if (!R::testConnection()) {
            R::setup('sqlite:minesweeper.db');
            R::ext('xdispense', function ($type) {
                return R::dispense($type);
            });
        }
    }

    public function saveGame($gameState, $playerName)
    {
        $game = R::findOne('games', 'player_name = ?', [$playerName]);

        if (!$game) {
            $game = R::xdispense('games');
        }

        $game->date = date('Y-m-d H:i:s');
        $game->player_name = $playerName;
        $game->width = $gameState['width'];
        $game->height = $gameState['height'];
        $game->mines = $gameState['mines'];
        $game->board = json_encode($gameState['board']);
        $game->revealed = json_encode($gameState['revealed']);
        $game->gameOver = $gameState['gameOver'];

        return R::store($game);
    }

    public function loadGame($gameId)
    {
        $game = R::load('games', $gameId);

        if ($game->id) {
            return [
            'date' => $game->date,             // Добавляем date
            'player_name' => $game->player_name, // Добавляем player_name
            'width' => $game->width,
            'height' => $game->height,
            'mines' => $game->mines,
            'board' => json_decode($game->board, true),
            'revealed' => json_decode($game->revealed, true),
            'gameOver' => $game->gameOver
            ];
        }

        return null;
    }


    public function saveMove($gameId, $moveNumber, $x, $y, $result)
    {
        if (!$this->gameExists($gameId)) {
            \cli\line("Error: Game with ID $gameId does not exist.");
            return;
        }

        $move = R::xdispense('moves');
        $move->game_id = $gameId;
        $move->move_number = $moveNumber;
        $move->x = $x;
        $move->y = $y;
        $move->result = $result;

        R::store($move);

        \cli\line("Move saved: Game ID: $gameId, Move Number: $moveNumber, Coordinates: ($x, $y), Result: $result");
    }

    private function gameExists($gameId)
    {
        return (bool)R::count('games', 'id = ?', [$gameId]);
    }

    public function listGames()
    {
        $games = R::findAll('games');
        $result = [];

        foreach ($games as $game) {
            $result[] = [
                'id' => $game->id,
                'date' => $game->date,
                'player_name' => $game->player_name,
                'width' => $game->width,
                'height' => $game->height,
                'mines' => $game->mines,
                'gameOver' => $game->gameOver
            ];
        }

        return $result;
    }

    public function replayGame($gameId)
    {
        $game = $this->loadGame($gameId);
        if (!$game) {
              echo "Game not found!\n";
              return;
        }

        echo "Stories of {$game['player_name']} game\n";
        echo "ID: $gameId | Date: {$game['date']} | Player: {$game['player_name']} | Size: {$game['width']}x{$game['height']} | Mines: {$game['mines']} | Status: " . ($game['gameOver'] ? 'Finished' : 'In Progress') . "\n";

     // Получаем ходы и корректируем нумерацию для отображения с 1
        $moves = R::findAll('moves', 'game_id = ? ORDER BY move_number', [$gameId]);

        $displayedMoveNumber = 1;
        foreach ($moves as $move) {
             echo "Move #$displayedMoveNumber: ({$move->x}, {$move->y}) - {$move->result}\n";
             $displayedMoveNumber++;
        }
    }
}
