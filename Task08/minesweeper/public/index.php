<?php
require __DIR__ . '/../vendor/autoload.php';
use Slim\Factory\AppFactory;
use GuzzleHttp\Psr7\Utils;

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

// Редирект на index.html
$app->get('/', function ($request, $response) {
    return $response
        ->withHeader('Location', '/index.html')
        ->withStatus(302);
});

// Получение всех игр
$app->get('/games', function ($request, $response) {
    $db = new SQLite3(__DIR__ . '/../db/games.db');
    $result = $db->query('SELECT * FROM games');
    $games = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $games[] = $row;
    }
    return $response->withHeader('Content-Type', 'application/json')
        ->withBody(Utils::streamFor(json_encode($games)));
});

// Получение игры с шагами
$app->get('/games/{id}', function ($request, $response, $args) {
    $db = new SQLite3(__DIR__ . '/../db/games.db');
    $id = (int)$args['id'];
    $gameResult = $db->querySingle("SELECT * FROM games WHERE id = $id", true);
    $stepsResult = $db->query("SELECT * FROM steps WHERE game_id = $id");
    $steps = [];
    while ($row = $stepsResult->fetchArray(SQLITE3_ASSOC)) {
        $steps[] = $row;
    }
    if ($gameResult) {
        $gameResult['moves'] = $steps;
        return $response->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor(json_encode($gameResult)));
    }
    return $response->withStatus(404)->write('Game not found');
});

// Добавление новой игры
$app->post('/games', function ($request, $response) {
    $db = new SQLite3(__DIR__ . '/../db/games.db');
    $data = json_decode($request->getBody()->getContents(), true);
    $stmt = $db->prepare('INSERT INTO games (playerName, size, mines, winStatus, timestamp) VALUES (?, ?, ?, ?, ?)');
    $stmt->bindValue(1, $data['playerName']);
    $stmt->bindValue(2, $data['size']);
    $stmt->bindValue(3, $data['mines']);
    $stmt->bindValue(4, $data['winStatus']);
    $stmt->bindValue(5, $data['timestamp']);
    $stmt->execute();
    $id = $db->lastInsertRowID();
    return $response->withHeader('Content-Type', 'application/json')
        ->withBody(Utils::streamFor(json_encode(['id' => $id])));
});

// Добавление хода
$app->post('/step/{id}', function ($request, $response, $args) {
    $db = new SQLite3(__DIR__ . '/../db/games.db');
    $id = (int)$args['id'];
    $data = json_decode($request->getBody()->getContents(), true);
    $stmt = $db->prepare('INSERT INTO steps (game_id, row, col, result) VALUES (?, ?, ?, ?)');
    $stmt->bindValue(1, $id);
    $stmt->bindValue(2, $data['row']);
    $stmt->bindValue(3, $data['col']);
    $stmt->bindValue(4, $data['result']);
    $stmt->execute();
    return $response->withStatus(201);
});

// Обновление статуса игры
$app->patch('/games/{id}', function ($request, $response, $args) {
    $db = new SQLite3(__DIR__ . '/../db/games.db');
    $id = (int)$args['id'];
    $data = json_decode($request->getBody()->getContents(), true);
    $stmt = $db->prepare('UPDATE games SET winStatus = ? WHERE id = ?');
    $stmt->bindValue(1, $data['winStatus']);
    $stmt->bindValue(2, $id);
    $stmt->execute();
    return $response->withStatus(200);
});

// Очистка базы данных
$app->post('/clear-db', function ($request, $response) {
    $db = new SQLite3(__DIR__ . '/../db/games.db');
    $db->exec('DELETE FROM games');
    $db->exec('DELETE FROM steps');
    return $response->withStatus(200);
});

$app->run();
