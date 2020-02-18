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

namespace TicTacToe\Core;

use TicTacToe\Board\Board;
use TicTacToe\Game\ResultChecker;
use TicTacToe\User\MiniMaxBot;
use TicTacToe\User\User;

class Response
{
    protected Board $board;

    protected ResultChecker $resultChecker;

    protected ?User $user;

    protected MiniMaxBot $bot;

    public function __construct()
    {
        $this->board = new Board();
        $this->resultChecker = new ResultChecker();
        $this->user = (new User())->get();
        $this->bot = new MiniMaxBot();
    }

    public function send()
    {
        header('Content-Type: application/json');
        $game = null;
        $board = $this->board->getBoard();

        $winner = null;
        $coordinates = $this->resultChecker->getWinner($board, $winner);
        if ($this->resultChecker->isGameOver($board) || $winner !== null) {
            $game = [
                'winner'      => $winner,
                'coordinates' => $coordinates
            ];
        }

        $users = [
            'bot' => [
                'userName' => $this->bot->getUserName(),
                'symbol'   => $this->bot->getSymbol()
            ],
        ];

        if (!empty($this->user)) {
            $users['player'] = [
                'userName' => $this->user->getUserName(),
                'symbol'   => $this->user->getSymbol()
            ];
        }

        $data = [
            'board' => $board,
            'game'  => $game,
            'users' => $users,
        ];
        echo json_encode($data);
        exit;
    }
}
