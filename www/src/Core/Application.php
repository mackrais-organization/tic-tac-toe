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


class Application
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function run()
    {
        $router = new Router();
        $this->setRoutes($router);
        $router->dispatch();
    }

    protected function setRoutes(Router $router)
    {
        if ($this->hasConfig('routes')) {
            $routes = $this->getRoutes();
            foreach ($routes as $item) {
                $router->add($item['rule'], $item['params']);
            }
        }
    }

    protected function getRoutes(): array
    {
        return $this->config['routes'] ?? [];
    }

    protected function hasConfig(string $name)
    {
        return !empty($this->config[$name]);
    }
}
