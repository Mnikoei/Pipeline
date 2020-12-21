<?php


class Pipe2
{
    public function show($passable, $pipeline)
    {
        if ($passable === 'previousInPipeTwo1') {
            return $pipeline->previousPipe($passable);
        }

        if ($passable === 'loopBetweenTwoAndOne') {
            return $pipeline->previousPipe($passable);
        }

        return $pipeline->nextPipe($passable . '2');
    }
}