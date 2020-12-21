<?php


class Pipe3
{
    public function show($value, $pipeline)
    {
        return $pipeline->nextPipe($value . '3', $pipeline);
    }
}