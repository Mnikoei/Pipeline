<?php


use Mnikoei\Pipeline\Pipeline;

class Pipe4
{
    public function show($value, Pipeline $pipeline)
    {
        return $pipeline->nextPipe($value . '4');
    }
}