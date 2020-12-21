<?php


class Pipe1
{
    public function show($passable, $pipeline)
    {
        if ($passable === 'skipPipeTwo') {
            $pipeline->skipNext();
        }
        
        if ($passable === 'loopBetweenTwoAndOne') {
            return $pipeline->nextPipe($passable);
        }
        

        return $pipeline->nextPipe($passable . '1');
    }
}