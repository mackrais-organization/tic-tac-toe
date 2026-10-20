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

use TicTacToe\Core\Application;

try {
    session_start();
    chdir(dirname(__DIR__));

    if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
        $msg = 'Did you forgot to run `composer install`?' . PHP_EOL . 'Unable to load the "./vendor/autoload.php".';
        http_response_code(500);
        throw new RuntimeException($msg);
    }
    require __DIR__ . '/../vendor/autoload.php';
    $config = require  __DIR__ . '/../config/config.php';

    $application = new Application($config);
    $application->run();
} catch (Throwable $e) {
    header('Content-Type: application/json');
    $expected = $e instanceof \TicTacToe\Exception\InvalidValidation
        || $e instanceof \TicTacToe\Exception\InvalidBoardUser
        || $e instanceof \TicTacToe\Exception\NotAllowedException
        || $e instanceof \TicTacToe\Exception\NotFoundException;
    if (!$expected) {
        http_response_code(500);
        error_log((string) $e);
    }
    echo json_encode(['message' => $expected ? $e->getMessage() : 'Internal server error']);
}
