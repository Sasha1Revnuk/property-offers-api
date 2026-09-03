<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    #[Test]
    public function the_root_endpoint_returns_json_info(): void
    {
        $response = $this->getJson('/');

        $response->assertOk()
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
