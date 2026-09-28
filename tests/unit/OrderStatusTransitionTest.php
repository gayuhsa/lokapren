<?php

declare(strict_types=1);

namespace Tests\unit;

use App\Models\OrderDeliveryModel;
use App\Models\OrderModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class OrderStatusTransitionTest extends CIUnitTestCase
{
    public function testOrderFollowsTheHappyPathInOrder(): void
    {
        $model = new OrderModel();

        $path = [
            OrderModel::STATUS_PENDING,
            OrderModel::STATUS_CONFIRMED,
            OrderModel::STATUS_PROCESSING,
            OrderModel::STATUS_READY,
            OrderModel::STATUS_SHIPPED,
            OrderModel::STATUS_COMPLETED,
        ];

        for ($i = 0, $max = count($path) - 1; $i < $max; $i++) {
            $this->assertTrue(
                $model->canTransitionTo($path[$i], $path[$i + 1]),
                "{$path[$i]} should be allowed to transition to {$path[$i + 1]}."
            );
        }
    }

    public function testOrderCanBeCancelledBeforeItIsShipped(): void
    {
        $model = new OrderModel();

        $cancellable = [
            OrderModel::STATUS_PENDING,
            OrderModel::STATUS_CONFIRMED,
            OrderModel::STATUS_PROCESSING,
            OrderModel::STATUS_READY,
            OrderModel::STATUS_SHIPPED,
        ];

        foreach ($cancellable as $status) {
            $this->assertTrue(
                $model->canTransitionTo($status, OrderModel::STATUS_CANCELLED),
                "{$status} should be cancellable."
            );
        }
    }

    public function testOrderCannotSkipStatusesOrMoveBackwards(): void
    {
        $model = new OrderModel();

        $this->assertFalse($model->canTransitionTo(OrderModel::STATUS_PENDING, OrderModel::STATUS_SHIPPED));
        $this->assertFalse($model->canTransitionTo(OrderModel::STATUS_PENDING, OrderModel::STATUS_COMPLETED));
        $this->assertFalse($model->canTransitionTo(OrderModel::STATUS_SHIPPED, OrderModel::STATUS_PROCESSING));
        $this->assertFalse($model->canTransitionTo(OrderModel::STATUS_COMPLETED, OrderModel::STATUS_CANCELLED));
        $this->assertFalse($model->canTransitionTo(OrderModel::STATUS_CANCELLED, OrderModel::STATUS_PENDING));
    }

    public function testOrderRejectsUnknownStatuses(): void
    {
        $model = new OrderModel();

        $this->assertFalse($model->canTransitionTo('shipped_by_carrier', OrderModel::STATUS_COMPLETED));
        $this->assertFalse($model->canTransitionTo(OrderModel::STATUS_PENDING, 'delivered'));
    }

    public function testTerminalOrderStatusesHaveNoOutgoingTransitions(): void
    {
        $model = new OrderModel();

        $this->assertSame([], $model->allowedTransitions()[OrderModel::STATUS_COMPLETED]);
        $this->assertSame([], $model->allowedTransitions()[OrderModel::STATUS_CANCELLED]);
    }

    public function testDeliveryFollowsTheHappyPathInOrder(): void
    {
        $model = new OrderDeliveryModel();

        $path = [
            OrderDeliveryModel::STATUS_PENDING,
            OrderDeliveryModel::STATUS_PICKED_UP,
            OrderDeliveryModel::STATUS_IN_TRANSIT,
            OrderDeliveryModel::STATUS_OUT_FOR_DELIVERY,
            OrderDeliveryModel::STATUS_DELIVERED,
        ];

        for ($i = 0, $max = count($path) - 1; $i < $max; $i++) {
            $this->assertTrue(
                $model->canTransitionTo($path[$i], $path[$i + 1]),
                "{$path[$i]} should be allowed to transition to {$path[$i + 1]}."
            );
        }
    }

    public function testDeliveryCanFailFromAnyUnfinishedStatus(): void
    {
        $model = new OrderDeliveryModel();

        $failable = [
            OrderDeliveryModel::STATUS_PENDING,
            OrderDeliveryModel::STATUS_PICKED_UP,
            OrderDeliveryModel::STATUS_IN_TRANSIT,
            OrderDeliveryModel::STATUS_OUT_FOR_DELIVERY,
        ];

        foreach ($failable as $status) {
            $this->assertTrue(
                $model->canTransitionTo($status, OrderDeliveryModel::STATUS_FAILED),
                "{$status} should be allowed to fail."
            );
        }
    }

    public function testDeliveredDeliveryIsTerminalAndCannotBeCancelled(): void
    {
        $model = new OrderDeliveryModel();

        $this->assertSame([], $model->allowedTransitions()[OrderDeliveryModel::STATUS_DELIVERED]);
        $this->assertFalse($model->canTransitionTo(OrderDeliveryModel::STATUS_DELIVERED, OrderDeliveryModel::STATUS_FAILED));
    }

    public function testDeliveryProgressIsMonotonic(): void
    {
        $model = new OrderDeliveryModel();

        $path = [
            OrderDeliveryModel::STATUS_PENDING,
            OrderDeliveryModel::STATUS_PICKED_UP,
            OrderDeliveryModel::STATUS_IN_TRANSIT,
            OrderDeliveryModel::STATUS_OUT_FOR_DELIVERY,
            OrderDeliveryModel::STATUS_DELIVERED,
        ];

        $previous = -1;

        foreach ($path as $status) {
            $progress = $model->progressFor($status);

            $this->assertGreaterThan($previous, $progress, "Progress for {$status} should increase.");
            $previous = $progress;
        }

        $this->assertSame(100, $model->progressFor(OrderDeliveryModel::STATUS_DELIVERED));
    }

    public function testEveryAllowedStatusParticipatesInTheTransitionMap(): void
    {
        foreach ([new OrderModel(), new OrderDeliveryModel()] as $model) {
            $transitions = $model->allowedTransitions();

            foreach ($model->allowedStatuses() as $status) {
                $this->assertArrayHasKey($status, $transitions, "{$status} should have a transition entry.");
            }

            foreach ($transitions as $from => $targets) {
                $this->assertContains(
                    $from,
                    $model->allowedStatuses(),
                    "Transition source {$from} should be an allowed status."
                );

                foreach ($targets as $to) {
                    $this->assertContains(
                        $to,
                        $model->allowedStatuses(),
                        "Transition target {$to} should be an allowed status."
                    );
                }
            }
        }
    }
}
