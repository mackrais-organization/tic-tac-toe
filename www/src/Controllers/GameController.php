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

namespace TicTacToe\Controllers;

use TicTacToe\Board\Board;
use TicTacToe\Core\Response;
use TicTacToe\Core\Router;
use TicTacToe\Exception\InvalidValidation;
use TicTacToe\User\MiniMaxBot;
use TicTacToe\User\User;

class GameController
{
    /**
     * @var User
     */
    private readonly User $user;
    /**
     * @var Board
     */
    private readonly Board $board;

    public function __construct()
    {
        $this->user = new User();
        $this->board = new Board();
    }

    public function indexAction()
    {
        $this->getResponse()->send();
    }

    public function startAction(Router $router)
    {
        $params = $router->getRequestBody();
        foreach (['username', 'symbol', 'level'] as $field) {
            if (!isset($params[$field]) || !is_string($params[$field])) {
                throw new InvalidValidation('Invalid ' . $field);
            }
        }
        if ($this->user->get() === null) {
            $this->user->create($params['username'], $params['symbol'], $params['level']);
            $this->board->clear();
            $this->board->setUser($this->user);
            if ($params['symbol'] === Board::SYMBOL_O) {
                $bot = new MiniMaxBot();
                $bot->makeMove($this->board);
            }
        }

        $this->getResponse()->send();
    }

    public function makeMoveAction(Router $router)
    {
        $params = $router->getRequestBody();
        foreach (['rowIndex', 'columnIndex'] as $field) {
            if (!isset($params[$field]) || !is_int($params[$field])) {
                throw new InvalidValidation('Incorrect coordinates');
            }
        }
        $this->board->makeMove(
            $params['rowIndex'] ?? null,
            $params['columnIndex'] ?? null
        );
        $this->getResponse()->send();
    }

    public function restartAction()
    {
        $this->board->clear();
        $this->user->logout();
        $this->getResponse()->send();
    }

    protected function getResponse()
    {
        return new Response();
    }
}
