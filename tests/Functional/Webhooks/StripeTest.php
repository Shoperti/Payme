<?php

namespace Shoperti\Tests\PayMe\Functional\Webhooks;

class StripeTest extends AbstractTest
{
    protected $gatewayData = [
        'config'     => 'stripe',
        'isRedirect' => true,
    ];

    /** @test */
    public function it_should_retrieve_webhooks_list()
    {
        $hooks = $this->getPayMe()->webhooks()->all();

        $this->assertNotEmpty($hooks[0]->data());
        $this->assertInternalType('array', $hooks[0]->data());
    }

    /** @test */
    public function it_should_create_a_webhook()
    {
        $hook = $this->getPayMe()->webhooks()->create([
            'url'            => 'https://example.com',
            'enabled_events' => ['*'],
        ]);

        $this->assertTrue($hook->success());
        $this->assertEquals('authorized', $hook->status());

        return $hook->reference();
    }

    /**
     * @test
     * @depends it_should_create_a_webhook
     */
    public function it_should_delete_a_webhook($hookId)
    {
        $hook = $this->getPayMe()->webhooks()->delete($hookId);

        $this->assertTrue($hook->success());
    }
}
