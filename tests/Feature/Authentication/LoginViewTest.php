<?php

declare(strict_types=1);

namespace Tests\Feature\Authentication;

use Tests\TestCase;

class LoginViewTest extends TestCase
{
    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Civil Service Review 2027');
        $response->assertSee('reviewer@csc2027.ph');
    }
}
