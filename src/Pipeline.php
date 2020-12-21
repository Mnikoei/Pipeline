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
        
    }

    public function thenReturn()
    {
        return $this->nextPipe($this->passable);
    }

    /**
     * @param $passable
     * @return mixed
     */
    public function nextPipe($passable)
    {
        $this->next();
        return $this->go($passable);
    }

    /**
     * @param $passable
     * @return mixed
     */
    public function previousPipe($passable)
    {
        $this->previous();
        return $this->go($passable);
    }

    public function go($passable)
    {
        $this->checkLoop();

        return $this->valid()
            ? app($this->pipes[$this->pointer])->{$this->method}($passable, $this)
            : $passable;
    }

    public function skipNext()
    {
        $this->next();
    }

    public function checkLoop()
    {
        static $pipeCalls = [];

        $calls = isset($pipeCalls[$this->current()]) ?
            ++$pipeCalls[$this->current()]
            : $pipeCalls[$this->current()] = 0;

        if ($calls > 3) {

            $filePath = is_string($pipe = $this->current())
                ? $pipe
                : (new \ReflectionClass($pipe))->getFileName();

            $message = 'Looks like infinite loop occurred at ' . $filePath;

            throw new LoopException($message);
        }
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
        return ++$this->pointer;
    }

    /**
     * @inheritDoc
     */
    public function previous()
    {
        return --$this->pointer;
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