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
        echo "<pre>$msg</pre>";
        throw new RuntimeException($msg);
    }
    require __DIR__ . '/../vendor/autoload.php';
    $config = require  __DIR__ . '/../config/config.php';

    $application = new Application($config);
    $application->run();

} catch (Throwable $e) {
    echo json_encode([
        'message' => $e->getMessage(),
        'code'    => $e->getCode()
    ]);
}
