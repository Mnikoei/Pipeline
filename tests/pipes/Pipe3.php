<?php

namespace Mnikoei\Pipeline\Tests\Pipes;

class Pipe3
{
    public function show($value, $pipeline)
    {
        return $pipeline($value . '3');
    }
}