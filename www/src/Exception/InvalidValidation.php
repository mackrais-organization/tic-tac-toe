<?php
/**
 * Created by PhpStorm.
 * PHP Version: 7.4
 *
 * @category
 * @author     Oleh Boiko <developer@mackais.com>
 * @copyright  2018-2020 @MackRais
 * @link       <https://mackrais.com>
 * @date       2/17/20
 */

declare(strict_types=1);

namespace TicTacToe\Exception;

use Exception;
use Throwable;

class InvalidValidation extends Exception
{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        http_response_code(422);
        parent::__construct($message, $code, $previous);
    }
}
