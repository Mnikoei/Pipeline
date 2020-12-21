<?php

use Mnikoei\Pipeline\LoopException;
use Mnikoei\Pipeline\Pipeline;
use Tests\TestCase;

class pipelineTest extends TestCase
{
    protected $pipeline;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pipeline = new Pipeline();
    }

    /**
     * @test
     */
    public function iteratesNormal()
    {
        $result = $this->pipeline
            ->send('')
            ->through([Pipe1::class, Pipe2::class, Pipe3::class])
            ->via('show')
            ->thenReturn();

        $this->assertSame($result, '123');
    }

    /**
     * @test
     */
    public function canSendDataToPreviousPipe()
    {
        $result = $this->pipeline
            ->send('previousInPipeTwo')
            ->through([Pipe1::class, Pipe2::class, Pipe3::class])
            ->via('show')
            ->thenReturn();

        $this->assertSame($result, 'previousInPipeTwo1123');
    }

    /**
     * @test
     */
    public function canSkipNextPipe()
    {
        $result = $this->pipeline
            ->send('skipPipeTwo')
            ->through([Pipe1::class, Pipe2::class, Pipe3::class])
            ->via('show')
            ->thenReturn();

        $this->assertSame($result, 'skipPipeTwo13');
    }

    /**
     * @test
     */
    public function avoidsInfiniteLoops()
    {
        $this->expectException(LoopException::class);

        $this->pipeline
            ->send('loopBetweenTwoAndOne')
            ->through([Pipe1::class, Pipe2::class, Pipe3::class])
            ->via('show')
            ->thenReturn();
    }

    /**
     * @test
     */
    public function canJumpToArbitraryPipe()
    {
        $this->expectException(LoopException::class);

        $result = $this->pipeline
            ->send('jumpToFour')
            ->through([Pipe1::class, Pipe2::class, Pipe3::class, Pipe4::class])
            ->via('show')
            ->thenReturn();

        $this->assertSame($result, 'jumpToFour14');
    }
}