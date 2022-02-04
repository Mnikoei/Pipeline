<?php

namespace Mnikoei\Pipeline;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
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
    protected $loopPossibility = false;

    /**
     * Determines how many times
     * loop can repeat
     *
     * @var bool
     */
    protected $loopRepetition = 2;

    /**
     * The pointer that indicates current pipe
     *
     * @var int
     */
    protected $pointer = -1;

    protected $pipeCalls = [];

    protected $container;

    public function __construct(ContainerContract $container = null)
    {
        $this->container = $container;
    }

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

    /**
     * @return mixed
     */
    public function thenReturn()
    {
        return $this->start();
    }

    /**
     * @param int $repetition
     * @return Pipeline
     */
    public function loopRepetition(int $repetition): Pipeline
    {
        $this->loopRepetition = $repetition;

        return $this;
    }

    /**
     * Passing data to next pipe can
     * be done by invoking pipeline object
     *
     * @param $passable
     * @return mixed
     * @throws LoopException
     */
    public function __invoke($passable)
    {
        return $this->nextPipe($passable);
    }

    /**
     * Starts pipeline by invoking
     * by passing data to first pipe
     *
     * @return mixed
     * @throws LoopException
     */
    public function start()
    {
        return $this->nextPipe($this->passable);
    }

    /**
     * Pass the passable data forward to nex pipe
     *
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
     * Pass back the passable data to previous pipe
     *
     * @param $passable
     * @return mixed
     * @throws LoopException
     */
    public function previousPipe($passable)
    {
        $this->loopPossibility = true;

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

            $pipe = $this->pipes[$this->pointer];

            $pipe = is_string($pipe) ? $this->resolve($pipe) : $pipe;

            return $pipe->{$this->method}($passable, $this);
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

            if (false === $pipeIndex) {
                throw new \UnexpectedValueException('Given pipe or pipe index is invalid!');
            }

        } elseif (is_int($pipe)) {
            $pipeIndex = $pipe;

            if ($pipe < -1 || $pipe > (count($this->pipes) - 1)) {
                throw new \UnexpectedValueException('Given pipe or pipe index is invalid!');
            }

        } else {
            throw new \UnexpectedValueException('Given pipe or pipe index is invalid!');
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
        if (! $this->loopPossibility){
            return;
        }

        $pipe = is_string($this->current())
            ? $this->current()
            : get_class($this->current());

        $calls = isset($this->pipeCalls[$pipe]) ?
            ++ $this->pipeCalls[$pipe]
            :  $this->pipeCalls[$pipe] = 1;

        if ($calls >= $this->loopRepetition) {

            throw new LoopException('Looks like infinite loop occurred at ' . $pipe);
        }
    }

    /**
     * @return bool
     */
    public function loopPossibility(): bool
    {
        return $this->loopPossibility;
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

    public function resolve($name)
    {
        if (! $this->container) {
            $this->container = Container::getInstance();
        }

        return $this->container->make($name);
    }
}