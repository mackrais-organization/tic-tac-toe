<?php

declare(strict_types=1);

namespace TicTacToe\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TicTacToe\Core\Router;
use TicTacToe\Exception\NotAllowedException;
use TicTacToe\Exception\NotFoundException;

final class RouterTest extends TestCase
{
    private array $server;
    private array $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->server = $_SERVER;
        $this->post = $_POST;
        $_SERVER = ['REQUEST_URI' => '/unit-route', 'REQUEST_METHOD' => 'GET'];
        $_POST = [];
        http_response_code(200);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_POST = $this->post;
        unset($this->server, $this->post);
        http_response_code(200);
        parent::tearDown();
    }

    #[Test]
    public function doesNotShareRoutesBetweenInstances(): void
    {
        $first = new Router();
        $first->add('^unit-route$', ['controller' => 'Unknown']);
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Route not found');
        new Router()->dispatch();
    }

    #[Test]
    public function returnsBodyFieldsAndFallback(): void
    {
        $_POST = ['username' => 'Player'];
        $router = new Router();
        $this->assertSame($_POST, $router->getRequestBody());
        $this->assertSame('Player', $router->getRequestBody('username'));
        $this->assertNull($router->getRequestBody('unknown'));
    }

    #[Test]
    public function returns404ForMissingRoute(): void
    {
        $this->expectException(NotFoundException::class);
        new Router()->dispatch();
    }

    #[Test]
    public function returns405ForWrongMethod(): void
    {
        $_SERVER['REQUEST_URI'] = '/only-post?query=value';
        $router = new Router();
        $router->add('^only-post$', ['controller' => 'Game', 'method' => ['POST']]);
        $this->expectException(NotAllowedException::class);
        $router->dispatch();
    }

    #[Test]
    public function rejectsMissingControllerAfterMatchingPath(): void
    {
        $_SERVER['REQUEST_URI'] = '/unknown-controller/';
        $router = new Router();
        $router->add('^unknown-controller$', ['controller' => 'Unknown', 'method' => ['GET']]);
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Controller');
        $router->dispatch();
    }
}
