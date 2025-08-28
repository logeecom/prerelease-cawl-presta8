<?php

namespace OnlinePayments\Core\Tests\Infrastructure\Utility\Events;

use OnlinePayments\Core\Infrastructure\Utility\Events\Event;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Utility\Events\TestBarEvent;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Utility\Events\TestEventEmitter;
use OnlinePayments\Core\Tests\Infrastructure\Common\TestComponents\Utility\Events\TestFooEvent;
use PHPUnit\Framework\TestCase;

/**
 * Class EventEmitterTest.
 *
 * @package OnlinePayments\Core\Tests\Infrastructure\Utility\Events
 */
class EventEmitterTest extends TestCase
{
    /**
     * @return void
     */
    public function testItShouldBePossibleToFireEventWithoutAnySubscribedHandlers()
    {
        $emitter = new TestEventEmitter();
        $ex = null;

        try {
            $emitter->fire(new TestFooEvent());
        } catch (\Exception $ex) {
            $this->fail('It should be possible to fire event without any subscribers.');
        }

        $this->assertEmpty($ex);
    }

    /**
     * @return void
     */
    public function testItShouldBePossibleToSubscribeMultipleHandlersToSameEvent()
    {
        $emitter = new TestEventEmitter();
        $handler1Event = null;
        $handler2Event = null;
        $emitter->when(
            TestFooEvent::CLASS_NAME,
            function (TestFooEvent $event) use (&$handler1Event) {
                $handler1Event = $event;
            }
        );
        $emitter->when(
            TestFooEvent::CLASS_NAME,
            function (TestFooEvent $event) use (&$handler2Event) {
                $handler2Event = $event;
            }
        );

        $emitter->fire(new TestFooEvent());

        $this->assertNotNull($handler1Event, 'Event emitter must call each subscribed handler.');
        $this->assertNotNull($handler2Event, 'Event emitter must call each subscribed handler.');
    }

    /**
     * @return void
     */
    public function testItShouldBePossibleToNotifyOnlySubscribersOnSpecificEvent()
    {
        $emitter = new TestEventEmitter();
        $handler1Event = null;
        $handler2Event = null;
        $emitter->when(
            TestFooEvent::CLASS_NAME,
            function (TestFooEvent $event) use (&$handler1Event) {
                $handler1Event = $event;
            }
        );
        $emitter->when(
            TestBarEvent::CLASS_NAME,
            function (Event $event) use (&$handler2Event) {
                $handler2Event = $event;
            }
        );

        $emitter->fire(new TestFooEvent());

        $this->assertNotNull($handler1Event, 'Event emitter must call each subscribed handler.');
        $this->assertNull($handler2Event, 'Event emitter must call only handlers subscribed to fired event.');
    }

    /**
     * @return void
     */
    public function testItShouldBePossibleToTriggerExceptionFromInsideHandlerMethod()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Handler exception');
        $emitter = new TestEventEmitter();
        $emitter->when(
            TestFooEvent::CLASS_NAME,
            function () {
                throw new \RuntimeException('Handler exception');
            }
        );

        $emitter->fire(new TestFooEvent());

        $this->fail('It should be possible to throw exception from event handler code.');
    }
}
