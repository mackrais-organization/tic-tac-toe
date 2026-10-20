<?php

declare(strict_types=1);

namespace TicTacToe\Tests\Support;

use PHPUnit\Framework\TestCase;

abstract class SessionTestCase extends TestCase
{
    private array $savedSession;

    protected function setUp(): void
    {
        parent::setUp();
        $this->savedSession = $_SESSION ?? [];
        $_SESSION = [];
        http_response_code(200);
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->savedSession;
        unset($this->savedSession);
        http_response_code(200);
        parent::tearDown();
    }
}
