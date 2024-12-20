document.addEventListener('DOMContentLoaded', function () {
    let width, height, mines, gameBoard, cells, gameStatus, gameOver = false, playerName;
    let gameId = null;
    let moves = []; // Хранение шагов игрока

    const startBtn = document.getElementById('startBtn');
    const viewPastGamesBtn = document.getElementById('view-past-games');
    const helpBtn = document.getElementById('help');
    const closeHelpBtn = document.getElementById('close-help');
    const replayContainer = document.getElementById('replay-container');
    const closeReplayBtn = document.getElementById('close-replay');

    startBtn.addEventListener('click', startNewGame);
    viewPastGamesBtn.addEventListener('click', displayPastGames);
    helpBtn.addEventListener('click', () => {
        document.getElementById('help-container').style.display = 'block';
    });
    closeHelpBtn.addEventListener('click', () => {
        document.getElementById('help-container').style.display = 'none';
    });
    closeReplayBtn.addEventListener('click', () => {
        replayContainer.style.display = 'none';
    });

    async function startNewGame() {
        playerName = document.getElementById('player-name').value.trim();
        if (!playerName) {
            alert('Please enter your name before starting the game.');
            return;
        }

        width = parseInt(document.getElementById('width').value);
        height = parseInt(document.getElementById('height').value);
        mines = parseInt(document.getElementById('mines').value);

        gameBoard = document.getElementById('game-board');
        gameStatus = document.getElementById('game-status');
        gameOver = false;
        moves = []; // Сброс шагов при новой игре

        gameBoard.innerHTML = '';
        gameStatus.textContent = '';
        cells = [];
        const minePositions = generateMines(width * height, mines);

        createBoard(width, height, minePositions);
        document.querySelector('.game-container').style.display = 'block';

        // Отправляем данные о новой игре на сервер
        const response = await fetch('/games', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                playerName,
                size: `${width}x${height}`,
                mines,
                winStatus: 'In Progress',
                timestamp: new Date().toISOString()
            })
        });
        const result = await response.json();
        gameId = result.id;
    }

    async function saveGameResult(winStatus) {
    if (gameId) {
        moves.forEach(async (move) => {
            await fetch(`/step/${gameId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    row: move.row,
                    col: move.col,
                    result: move.result
                })
            });
        });

        // Сохраняем статус игры
        await fetch(`/games/${gameId}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                winStatus: winStatus ? 'Won' : 'Lost'
            })
        });
    }
}


    async function displayPastGames() {
        const response = await fetch('/games');
        const games = await response.json();

        const gameList = document.getElementById('game-list');
        gameList.innerHTML = '';
        games.forEach(game => {
            const listItem = document.createElement('li');
            const timestamp = new Date(game.timestamp).toLocaleString('ru-RU', { timeZone: 'Europe/Moscow' });
            listItem.textContent = `Player: ${game.playerName} - Game on ${timestamp} - Size: ${game.size} - Mines: ${game.mines} - Result: ${game.winStatus}`;
            listItem.addEventListener('click', () => replayGame(game.id));
            gameList.appendChild(listItem);
        });
    }

    async function replayGame(id) {
    const response = await fetch(`/games/${id}`);
    const gameData = await response.json();

    replayContainer.style.display = 'block';
    const replayList = document.getElementById('replay-list');
    replayList.innerHTML = `<strong>Game Replay:</strong> Player: ${gameData.playerName} | Size: ${gameData.size} | Mines: ${gameData.mines} | Result: ${gameData.winStatus}`;

    if (gameData.moves && gameData.moves.length > 0) {
        const movesList = document.createElement('ul');
        gameData.moves.forEach((move, index) => {
            const moveItem = document.createElement('li');
            moveItem.textContent = `Move ${index + 1}: ${move.row}x${move.col} - ${move.result}`;
            movesList.appendChild(moveItem);
        });
        replayList.appendChild(movesList);
    } else {
        const noMoves = document.createElement('p');
        noMoves.textContent = 'No moves recorded for this game.';
        replayList.appendChild(noMoves);
    }
}


    function createBoard(width, height, minePositions) {
        for (let i = 0; i < height; i++) {
            for (let j = 0; j < width; j++) {
                const cell = document.createElement('div');
                cell.classList.add('cell');
                cell.dataset.index = i * width + j;
                gameBoard.appendChild(cell);
                cells.push(cell);

                cell.addEventListener('click', () => {
                    if (gameOver) return;
                    handleCellClick(cell, minePositions);
                });
            }
        }
        gameBoard.style.gridTemplateColumns = `repeat(${width}, 40px)`;
        gameBoard.style.gridTemplateRows = `repeat(${height}, 40px)`;
    }

    function generateMines(totalCells, mineCount) {
        const minePositions = new Set();
        while (minePositions.size < mineCount) {
            minePositions.add(Math.floor(Math.random() * totalCells));
        }
        return minePositions;
    }

    async function handleCellClick(cell, minePositions) {
        if (gameOver) return;

        const index = parseInt(cell.dataset.index);
        const row = Math.floor(index / width);
        const col = index % width;

        if (minePositions.has(index)) {
            cell.classList.add('revealed', 'mine');
            moves.push({ row, col, result: 'Mine' });
            gameOver = true;
            gameStatus.textContent = 'Game Over!';
            await saveGameResult(false);
            return;
        }

        moves.push({ row, col, result: 'Safe' });
        revealCell(cell, minePositions);

        if (checkForWin(minePositions)) {
            gameStatus.textContent = 'You Win!';
            gameOver = true;
            await saveGameResult(true);
        }
    }

    function revealCell(cell, minePositions) {
        if (cell.classList.contains('revealed')) return;
        cell.classList.add('revealed');
        const adjacentMines = countAdjacentMines(parseInt(cell.dataset.index), minePositions);

        if (adjacentMines > 0) {
            cell.textContent = adjacentMines;
        } else {
            revealAdjacentCells(parseInt(cell.dataset.index), minePositions);
        }
    }

    function countAdjacentMines(index, minePositions) {
        const neighbors = [-1, 1, -width, width, -width - 1, -width + 1, width - 1, width + 1];
        return neighbors.reduce((count, offset) => count + (minePositions.has(index + offset) ? 1 : 0), 0);
    }

    function revealAdjacentCells(index, minePositions) {
        const neighbors = [-1, 1, -width, width, -width - 1, -width + 1, width - 1, width + 1];
        neighbors.forEach(offset => {
            const neighborIndex = index + offset;
            const neighborCell = cells[neighborIndex];
            if (neighborCell && !neighborCell.classList.contains('revealed') && !minePositions.has(neighborIndex)) {
                revealCell(neighborCell, minePositions);
            }
        });
    }

    function checkForWin(minePositions) {
        return cells.every(cell => {
            const index = parseInt(cell.dataset.index);
            return minePositions.has(index) || cell.classList.contains('revealed');
        });
    }
    
    const clearDbBtn = document.getElementById('clear-db');
    clearDbBtn.addEventListener('click', clearDatabase);

    async function clearDatabase() {
        const confirmClear = confirm('Are you sure you want to clear the database?');
        if (confirmClear) {
            const response = await fetch('/clear-db', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            if (response.ok) {
                alert('Database cleared successfully.');
                displayPastGames(); // Обновить список игр после очистки базы данных
            } else {
                alert('Failed to clear the database.');
            }
        }
    }

});
