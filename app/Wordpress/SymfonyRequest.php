<?php

namespace App\Wordpress;

use Symfony\Component\HttpFoundation\Request as BaseRequest;

class SymfonyRequest extends BaseRequest
{
    public static function createFromGlobals($uri=null): static
    {
        // 
        $server = $_SERVER;
        $newServer = array_merge($server, [
            'REQUEST_URI' => $uri,
            'QUERY_STRING' => $uri,
        ]);

        // origin + $newServer
        $request = self::createRequestFromFactory($_GET, $_POST, [], $_COOKIE, $_FILES, $newServer);

        if (str_starts_with($request->headers->get('CONTENT_TYPE', ''), 'application/x-www-form-urlencoded')
            && \in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), ['PUT', 'DELETE', 'PATCH'])
        ) {
            parse_str($request->getContent(), $data);
            $request->request = new InputBag($data);
        }

        return $request;
    }

    private static function createRequestFromFactory(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], $content = null): static
    {
        if (self::$requestFactory) {
            $request = (self::$requestFactory)($query, $request, $attributes, $cookies, $files, $server, $content);

            if (!$request instanceof self) {
                throw new \LogicException('The Request factory must return an instance of Symfony\Component\HttpFoundation\Request.');
            }

            return $request;
        }

        return new static($query, $request, $attributes, $cookies, $files, $server, $content);
    }
}