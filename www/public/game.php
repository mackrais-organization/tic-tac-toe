<?php
/**
 * Created by PhpStorm.
 * PHP Version: 7.4
 *
 * @category
 * @author     Oleh Boiko <developer@mackais.com>
 * @copyright  2014-2019 MackRais
 * @link       <https://mackrais.com>
 * @date       2020-02-17
 */
declare(strict_types=1);

use TicTacToe\Board\Board;
use TicTacToe\Core\Response;
use TicTacToe\User\User;


try {
    session_start();
    chdir(dirname(__DIR__));

    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        $msg = 'Did you forgot to run `composer install`?' . PHP_EOL . 'Unable to load the "./vendor/autoload.php".';
        http_response_code(500);
        echo "<pre>$msg</pre>";
        throw new RuntimeException($msg);
    }
    require __DIR__ . '/../vendor/autoload.php';

    $jsonInput = json_decode(file_get_contents('php://input'), true);

    $action = $jsonInput['action'] ?? $_REQUEST['action'] ?? '';

    $board = new Board();
    $user = new User();

    switch ($action) {
        case "create-user":
            $symbol = $jsonInput['symbol'] ?? null;
            $user->create($jsonInput['username'] ?? '', $symbol);
            if($symbol === Board::SYMBOL_O){
                $board->setUser($user->get());
                $bot = new \TicTacToe\User\MiniMaxBot();
                $bot->setUser($user->get());
                $bot->makeMove($board);
            }
            break;
        case "make-a-move":
            $board->makeMove(
                $jsonInput['rowIndex'] ?? null,
                $jsonInput['columnIndex'] ?? null
            );
            break;
        case "restart":
            $board->clear();
            $user->logout();
            break;
    }

    $response = new Response();
    $response->send();
} catch (Throwable $e) {
    echo json_encode([
        'message' => $e->getMessage(),
        'code'    => $e->getCode(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTrace()
    ]);
}




