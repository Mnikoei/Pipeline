<?php

use Mnikoei\Pipeline\LoopException;
use Mnikoei\Pipeline\Pipeline;
use Tests\TestCase;

/**
 * Class pipelineTest
 * @runTestsInSeparateProcesses
 */

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
    public function watchesInfiniteLoopOnceJumpedToOneOfPreviousPipes()
    {
        $this->pipeline->previousPipe('whatever');

        $this->assertTrue($this->pipeline->loopPossibility());
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
    public function canConfigureLoopTimes()
    {
        $pipe1 = Mockery::spy(Pipe1::class)->makePartial();

        try {

            $this->pipeline
                ->send('loopBetweenTwoAndOne')
                ->through([$pipe1, Pipe2::class])
                ->via('show')
                ->loopRepetition(5)
                ->thenReturn();

        } catch (Exception $e) {}

        $pipe1->shouldHaveReceived('show')->times(5);
    }

    /**
     * @test
     */
    public function canJumpToArbitraryPipe()
    {
        $result = $this->pipeline
            ->send('jumpFromOneToFour')
            ->through([Pipe1::class, Pipe2::class, Pipe3::class, Pipe4::class])
            ->via('show')
            ->thenReturn();

        $this->assertSame($result, 'jumpFromOneToFour14');
    }

    /**
     * @test
     */
    public function failsIfPipeIndexWasOutOfRange()
    {
        $this->expectException(UnexpectedValueException::class);

        $this->pipeline
            ->send('invalidPipeIndex')
            ->through([Pipe1::class, Pipe2::class, Pipe3::class, Pipe4::class])
            ->via('show')
            ->thenReturn();
    }

    /**
     * @test
     */
    public function canJumpToArbitraryPipeByGivingPipeClass()
    {
        $result = $this->pipeline
            ->send('jumpFromOneToFourByClass')
            ->through([Pipe1::class, Pipe2::class, Pipe3::class, Pipe4::class])
            ->via('show')
            ->thenReturn();

        $this->assertSame($result, 'jumpFromOneToFourByClass14');
    }

    /**
     * @test
     */
    public function failsIfClassWasNotValid()
    {
        $this->expectException(UnexpectedValueException::class);

        $this->pipeline
            ->send('invalidPipeClass')
            ->through([Pipe1::class, Pipe2::class, Pipe3::class, Pipe4::class])
            ->via('show')
            ->thenReturn();
    }
}