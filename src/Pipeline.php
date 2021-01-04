<?php

namespace Mnikoei\Pipeline;

use Closure;
use Illuminate\Contracts\Pipeline\Pipeline as PipelineContract;
use Iterator;

class Pipeline implements PipelineContract, Iterator
{

    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected $passable;

    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected $method = 'handle';

    /**
     * Indicates the pipeline to watch
     * Infinite loop occurrence
     *
     * @var bool
     */
    protected $loop = false;

    /**
     * The pointer that indicates current pipe
     *
     * @var int
     */
    protected $pointer = -1;

    public function send($passable)
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the array of pipes.
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    /**
     * Set the method to call on the pipes.
     *
     * @param  string  $method
     * @return $this
     */
    public function via($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param Closure $destination
     * @return mixed
     */
    public function then(Closure $destination)
    {
        return $destination();
    }

    public function thenReturn()
    {
        return $this->start();
    }

    public function __invoke($passable)
    {
        return $this->nextPipe($passable);
    }

    public function start()
    {
        return $this->nextPipe($this->passable);
    }

    /**
     * @param $passable
     * @return mixed
     * @throws LoopException
     */
    public function nextPipe($passable)
    {
        $this->next();

        return $this->pass($passable);
    }

    /**
     * Pass back the passable data to previous data
     *
     * @param $passable
     * @return mixed
     * @throws LoopException
     */
    public function previousPipe($passable)
    {
        $this->loop = true;

        $this->previous();

        return $this->pass($passable);
    }

    /**
     * @param $passable
     * @return mixed
     * @throws LoopException
     */
    public function pass($passable)
    {
        if ($this->valid()) {

            $this->watchLoop();

            return resolve($this->pipes[$this->pointer])->{$this->method}($passable, $this);
        }

        return $this->then(function () use ($passable) {
            return $passable;
        });
    }

    /**
     * @param $pipe
     * @param $passable
     * @return mixed
     * @throws LoopException
     */
    public function jumpTo($pipe, $passable)
    {
        if (is_string($pipe)) {
            $pipeIndex = array_search($pipe, $this->pipes, true);

        } elseif (is_int($pipe)) {
            $pipeIndex = $pipe;

        } else {
            throw new \UnexpectedValueException('Given pipe or index is not valid!');
        }

        if (false === $pipeIndex || ($pipe < -1 || $pipe > (count($this->pipes) - 1))) {
            throw new \UnexpectedValueException('Given pipe or index is not valid!');
        }

        $this->pointer = $pipeIndex;

        return $this->pass($passable);
    }

    /**
     * Skips next pipe
     */
    public function skipNext()
    {
        $this->next();
    }

    /**
     * Watches for infinite loop occurrence
     *
     * @throws LoopException
     */
    public function watchLoop()
    {
        if (! $this->loopPossibility()){
            return;
        }

        static $pipeCalls = [];

        $calls = isset($pipeCalls[$this->current()]) ?
            ++ $pipeCalls[$this->current()]
            :  $pipeCalls[$this->current()] = 0;

        if ($calls > 3) {

            $message = 'Looks like infinite loop occurred at ' . $this->current();

            throw new LoopException($message);
        }
    }

    /**
     * @return bool
     */
    public function loopPossibility(): bool
    {
        return $this->loop;
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->pipes[$this->pointer];
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        return ++ $this->pointer;
    }

    /**
     * @inheritDoc
     */
    public function previous()
    {
        return -- $this->pointer;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->pointer;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return isset($this->pipes[$this->pointer]);
    }

    public function rewind()
    {
        $this->pointer = 0;
    }
}