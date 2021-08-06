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
use TicTacToe\User\MiniMaxBot;
use TicTacToe\User\User;

class GameController
{
    /**
     * @var User
     */
    private User $user;
    /**
     * @var Board
     */
    private Board $board;

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
        $symbol = $params['symbol'] ?? null;
        $level = $params['level'] ?? '';
        $this->user->create($params['username'] ?? '', $symbol, $level);

        if($symbol === Board::SYMBOL_O){
            $this->board->setUser($this->user->get());
            $bot = new MiniMaxBot();
            $bot->setUser( $this->user->get());
            $bot->makeMove($this->board);
        }

        $this->getResponse()->send();
    }

    public function makeMoveAction(Router $router)
    {
        $params = $router->getRequestBody();
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
