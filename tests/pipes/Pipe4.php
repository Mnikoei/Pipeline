<?php


class Pipe4
{
    public function show($value, $next)
    {
        return $next($value.'3');
    }
}