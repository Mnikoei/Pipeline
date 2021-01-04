<?php


use Illuminate\Support\Arr;
use Mnikoei\Pipeline\Pipeline;

class Pipe1
{
    public function show($passable, Pipeline $pipeline)
    {
        if ($passable === 'skipPipeTwo') {
            $pipeline->skipNext();
        }
        
        if ($passable === 'loopBetweenTwoAndOne') {
            return $pipeline->nextPipe($passable);
        }

        if ($passable === 'jumpFromOneToFour') {
            return $pipeline->jumpTo(3, $passable . '1');
        }

        if ($passable === 'jumpFromOneToFourByClass') {
            return $pipeline->jumpTo(Pipe4::class, $passable . '1');
        }

        if ($passable === 'invalidPipeIndex') {
            return $pipeline->jumpTo(Arr::random([-2, 4]), $passable . '1');
        }

        if ($passable === 'invalidPipeClass') {
            return $pipeline->jumpTo('invalid namespace reference', $passable . '1');
        }

        return $pipeline($passable . '1');
    }
}