<?php

namespace Tests\Unit;

use App\Models\ReadySent;
use App\Repositories\ReadySentRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ReadySentRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_update_delegates_to_the_base_repository_without_recursion(): void
    {
        $model = Mockery::mock(ReadySent::class);
        $storedDelivery = Mockery::mock(ReadySent::class);

        $model->shouldReceive('find')
            ->once()
            ->with(42)
            ->andReturn($storedDelivery);

        $storedDelivery->shouldReceive('fill')
            ->once()
            ->with(['readMail' => 1])
            ->andReturnSelf();

        $storedDelivery->shouldReceive('save')
            ->once()
            ->andReturnTrue();

        $repository = new ReadySentRepository($model);

        $this->assertTrue($repository->update(42, ['readMail' => 1]));
    }
}
