<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    #[Test]
    public function the_root_endpoint_redirects_to_docs_when_enabled(): void
    {
        config(['api.docs_enabled' => true]);

        $this->get('/')
            ->assertRedirect('/api/documentation');
    }

    #[Test]
    public function the_root_endpoint_returns_json_info_when_docs_disabled(): void
    {
        config(['api.docs_enabled' => false]);

        $this->getJson('/')
            ->assertOk()
            ->assertJsonPath('message', 'Property Offers API');
    }

    #[Test]
    public function the_api_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }
}
