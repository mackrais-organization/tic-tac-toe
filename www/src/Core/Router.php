<?php
/**
 * Created by PhpStorm.
 * PHP Version: 7.4
 *
 * @category
 * @author     Oleh Boiko <developer@mackais.com>
 * @copyright  2018-2020 @MackRais
 * @link       <https://mackrais.com>
 * @date       2/19/20
 */

namespace TicTacToe\Core;

use TicTacToe\Exception\NotAllowedException;
use TicTacToe\Exception\NotFoundException;

class Router
{
    const METHOD_GET = 'GET';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_HEAD = 'HEAD';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    const METHOD_POST = 'POST';

    /**
     * Router table
     *
     * @var array
     */
    protected static array $routes = [];

    /**
     * Current router
     *
     * @var array
     */
    protected static array $route = [];

    protected string $requestUri;

    protected string $method;

    protected array $requestBodyParams = [];

    public function __construct()
    {
        $query = $_SERVER['REQUEST_URI'] ?? '';
        $this->requestUri =  rtrim(ltrim($query, '/'),'/');;

        $this->method = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else {
                if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                    $this->method = 'PUT';
                } else {
                    throw new \Exception("Unexpected Header");
                }
            }
        }

        $input = file_get_contents('php://input');
        if(!empty($input)){
            $this->requestBodyParams =  json_decode(($input), true);
        }else{
            $this->requestBodyParams = $_POST;
        }

        $this->method = strtoupper($this->method);
    }

    /**
     * Add rule route
     *
     * @param string $regexp
     * @param array  $route
     *
     * @return  void
     */
    public function add(string $regexp, array $route = []): void
    {
        self::$routes[] = ['regexp' => $regexp, 'route' => $route];
    }

    /**
     * Add item to router table
     *
     * @return array
     * @return  void
     */
    public function getRoutes(): array
    {
        return self::$routes;
    }

    /**
     * Get current route
     *
     * @return array
     */
    public function getRoute(): array
    {
        return self::$route;
    }

    /**
     * Search url in router table
     *
     * @param string $url
     *
     * @return bool
     * @throws NotAllowedException
     */
    protected function matchRoute(string $url): bool
    {
        $notAllowedError = null;

        foreach (self::$routes as  $item) {
            $pattern = $item['regexp'];
            $route = $item['route'];

            if (preg_match("#$pattern#i", $url, $matches)) {
                if (!empty($route['method']) && !in_array($this->method, $route['method'])) {
                    $notAllowedError =  'Not allowed method try next: ' . implode(',' , $route['method']);
                    continue;
                }

                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $route[$key] = $value;
                    }
                }

                if (isset($route['controller'])) {
                    $route['controller'] = $this->upperCamelCase($route['controller']);
                }
                if (!isset($route['action'])) {
                    $route['action'] = 'index';
                }
                self::$route = $route;
                return true;
            }
        }
        if(!empty($notAllowedError)){
            throw new NotAllowedException($notAllowedError);
        }
        return false;
    }

    /**
     * Replace url to controller path
     *
     * @param string $url
     *
     * @return mixed
     * @throws NotFoundException
     * @throws NotAllowedException
     */
    public function dispatch(): void
    {
        $url = $this->removeQueryString($this->requestUri);
        if ($this->matchRoute($url)) {
            $controller = 'TicTacToe\Controllers\\' . self::$route['controller'] . 'Controller';
            if (class_exists($controller)) {
                $controllerObj = new $controller(self::$route);
                $action = $this->upperCamelCase(self::$route['action'] . 'Action');
                if (method_exists($controllerObj, $action)) {
                    $controllerObj->$action($this);
                    return;
                } else {
                    throw new NotFoundException("Method $controller::$action not found");
                }
            } else {
                throw new NotFoundException("Controller $controller not found");
            }
        }
       throw new NotFoundException('Route not found');
    }

    public function getRequestBody($name = null)
    {
        if ($name !== null) {
            return $this->requestBodyParams[$name] ?? null;
        }
        return $this->requestBodyParams;
    }

    /**
     *
     *
     * @param string|null $string
     *
     * @return string
     */
    protected function upperCamelCase(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }

    /**
     * @param string|null $string
     *
     * @return string
     */
    protected function lowerCamelCase(string $string): string
    {
        return lcfirst($this->upperCamelCase($string));
    }

    /**
     * @param string $url
     *
     * @return string
     */
    protected function removeQueryString(string $url): string
    {
        $result = $url;
        if (!empty($result)) {
            $path = parse_url($result)['path'] ?? '';
            return rtrim($path, '/');
        }
        return $result;
    }
}
