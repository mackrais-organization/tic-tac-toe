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

http_response_code(410);
header('Content-Type: application/json');
echo json_encode(['message' => 'Use /api/v1/game instead.']);
