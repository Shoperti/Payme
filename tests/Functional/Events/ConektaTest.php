<?php

namespace Shoperti\Tests\PayMe\Functional\Events;

class ConektaTest extends AbstractTest
{
    protected $gatewayData = [
        'config' => 'conekta',
    ];

    /** @test */
    public function it_should_retrieve_all_events()
    {
        $events = $this->getPayMe()->events()->all();

        $this->assertNotEmpty($events[0]->data()['data']);
        $this->assertInternalType('array', $events[0]->data()['data']);

        return $events;
    }

    /**
     * @test
     *
     * @depends it_should_retrieve_all_events
     */
    public function it_should_retrieve_a_single_event($events)
    {
        $event = $this->getPayMe()->events()->find($events[0]->data()['id']);

        $this->assertCount(1, $event);
    }

    /** @test */
    public function it_should_fail_to_retrieve_a_nonexistent_event()
    {
        $event = $this->getPayMe()->events()->find('qiq');

        $this->assertEquals('failed', $event->status);
    }
}
