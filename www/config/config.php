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

use TicTacToe\Core\Router;

return [
    /**
     * // defaults routes
     * Router::add('^$',['controller'=> 'Main', 'action' => 'index']);
     * Router::add('^(?P<controller>[a-z-]+)/?(?P<action>[a-z-]+)?$');
     */
    'routes' => [
        [
            'rule' => '^api/v1/game$',
            'params' => [
                'controller' => 'Game',
                'action' => 'index',
                'method'=>[ Router::METHOD_GET, Router::METHOD_OPTIONS]
            ]
        ],
        [
            'rule' => '^api/v1/game$',
            'params' => [
                'controller' => 'Game',
                'action' => 'start',
                'method'=>[ Router::METHOD_POST,  Router::METHOD_OPTIONS]
            ]
        ],
        [
            'rule' => '^api/v1/game$',
            'params' => [
                'controller' => 'Game',
                'action' => 'makeMove',
                'method'=>[ Router::METHOD_PUT,  Router::METHOD_OPTIONS]
            ]
        ],
        [
            'rule' => '^api/v1/game$',
            'params' => [
                'controller' => 'Game',
                'action' => 'restart',
                'method'=>[ Router::METHOD_DELETE,  Router::METHOD_OPTIONS]
            ]
        ]
    ],
];
