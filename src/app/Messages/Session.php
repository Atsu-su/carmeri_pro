<?php

namespace App\Messages;

class Session
{
    public static function exists($key)
    {
        return session($key) ?? null;
    }
}