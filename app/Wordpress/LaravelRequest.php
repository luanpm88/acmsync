<?php

namespace App\Wordpress;

use Illuminate\Http\Request as BaseRequest;
use App\Wordpress\SymfonyRequest;

class LaravelRequest extends BaseRequest
{
    public static function capture($uri=null)
    {
        static::enableHttpMethodParameterOverride();

        return static::createFromBase(SymfonyRequest::createFromGlobals($uri));
    }
}