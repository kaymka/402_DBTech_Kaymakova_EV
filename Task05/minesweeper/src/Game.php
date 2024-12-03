<?php

namespace Kaymka\Minesweeper;

class Game
{
    // Основные параметры игры
    private $width;
    private $height;
    private $mines;
    private $board;
    private $revealed;
    private $gameOver;

    public function __construct($width, $height, $mines)
    {
        $this->width = $width;
        $this->height = $height;
        $this->mines = $mines;
        $this->initializeBoard();
        $this->gameOver = false;
    }

    private function initializeBoard()
    {
        $this->board = array_fill(0, $this->height, array_fill(0, $this->width, 0));
        $this->revealed = array_fill(0, $this->height, array_fill(0, $this->width, false));

        // Расстановка мин
        for ($i = 0; $i < $this->mines; $i++) {
            do {
                $x = rand(0, $this->width - 1);
                $y = rand(0, $this->height - 1);
            } while ($this->board[$y][$x] === 'M');
            $this->board[$y][$x] = 'M';
        }

        // Расчет чисел вокруг мин
        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if ($this->board[$y][$x] === 'M') {
                    continue;
                }
                $count = 0;
                for ($dy = -1; $dy <= 1; $dy++) {
                    for ($dx = -1; $dx <= 1; $dx++) {
                        $ny = $y + $dy;
                        $nx = $x + $dx;
                        if ($ny >= 0 && $ny < $this->height && $nx >= 0 && $nx < $this->width && $this->board[$ny][$nx] === 'M') {
                            $count++;
                        }
                    }
                }
                $this->board[$y][$x] = $count;
            }
        }
    }

    public function play($db, $playerName, $gameId = null)
    {
        $moveNumber = 0;

     // Если gameId не задан, создаем новую игру и получаем ее ID
        if ($gameId === null) {
              $gameState = [
            'width' => $this->width,
            'height' => $this->height,
            'mines' => $this->mines,
            'board' => $this->board,
            'revealed' => $this->revealed,
            'gameOver' => false
              ];
              $gameId = $db->saveGame($gameState, $playerName); // Сохраняем новую игру в БД
        }

        while (!$this->gameOver) {
             View\showBoard($this->board, $this->revealed);
             $input = \cli\prompt("Enter coordinates (x, y):");
             $input = trim($input);

            if (strpos($input, ',') === false) {
                \cli\line("Invalid input format. Please use 'x, y' format.");
                continue;
            }

            list($x, $y) = explode(',', $input);
            $x = (int)trim($x);
            $y = (int)trim($y);

            if ($x < 0 || $x >= $this->width || $y < 0 || $y >= $this->height) {
                \cli\line("Invalid coordinates!");
                continue;
            }

            $moveNumber++;
            $result = '';

            if ($this->board[$y][$x] === 'M') {
                $this->gameOver = true;
                $result = 'взорвался';
                \cli\line("Game Over! You hit a mine.");
            } else {
                $this->revealCell($x, $y);
                if ($this->checkWin()) {
                      $this->gameOver = true;
                      $result = 'выиграл';
                      \cli\line("Congratulations! You won!");
                } else {
                    $result = 'мины нет';
                }
            }

        // Сохранение хода в базу данных
            $db->saveMove($gameId, $moveNumber, $x, $y, $result);
            \cli\line("Move #$moveNumber: ($x, $y) - $result saved."); // Добавленный вывод
        }
        View\showBoard($this->board, $this->revealed);
    }



    public function revealCell($x, $y)
    {
        if ($this->revealed[$y][$x]) {
            return;
        }
        $this->revealed[$y][$x] = true;
        if ($this->board[$y][$x] === 0) {
            for ($dy = -1; $dy <= 1; $dy++) {
                for ($dx = -1; $dx <= 1; $dx++) {
                    $ny = $y + $dy;
                    $nx = $x + $dx;
                    if ($ny >= 0 && $ny < $this->height && $nx >= 0 && $nx < $this->width) {
                        $this->revealCell($nx, $ny);
                    }
                }
            }
        }
    }

    private function checkWin()
    {
        for ($y = 0; $y < $this->height; $y++) {
            for ($x = 0; $x < $this->width; $x++) {
                if ($this->board[$y][$x] !== 'M' && !$this->revealed[$y][$x]) {
                    return false;
                }
            }
        }
        return true;
    }

    // Добавленные методы для сохранения и загрузки игры

    public function getGameState()
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'mines' => $this->mines,
            'board' => $this->board,
            'revealed' => $this->revealed,
            'gameOver' => $this->gameOver
        ];
    }

    public function loadFromState($state)
    {
        $this->width = $state['width'];
        $this->height = $state['height'];
        $this->mines = $state['mines'];
        $this->board = $state['board'];
        $this->revealed = $state['revealed'];
        $this->gameOver = $state['gameOver'];
    }
}
