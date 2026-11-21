<?php

declare(strict_types=1);

namespace Tests\Fake;

use App\Http\Request;
use App\Http\RequestHandlerInterface;
use App\Http\Response;

/**
 * Handler kończący łańcuch middleware w testach. Zapamiętuje żądanie, które do niego dotarło.
 */
final class FixedResponseHandler implements RequestHandlerInterface
{
    public ?Request $received = null;

    public function __construct(private readonly Response $response = new Response('OK'))
    {
    }

    public function handle(Request $request): Response
    {
        $this->received = $request;

        return $this->response;
    }
}
