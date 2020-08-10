<?php

namespace Shoperti\Tests\PayMe\Functional\Events;

class StripeTest extends AbstractTest
{
    protected $gatewayData = [
        'config'     => 'stripe',
        'isRedirect' => true,
    ];

    /** @test */
    public function it_can_retrieve_all_and_single_events()
    {
        $events = $this->getPayMe()->events()->all();

        $this->assertNotEmpty($events[0]->data()['data']);
        $this->assertInternalType('array', $events[0]->data()['data']);

        $event = $this->getPayMe()->events()->find($events[0]->data()['id']);

        $this->assertTrue($event->success());
    }
}
