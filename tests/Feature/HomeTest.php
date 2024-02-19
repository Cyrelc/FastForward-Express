<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class HomeTest extends TestCase
{
    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testHomeRedirect() {
        $response = $this->get('/');
        // expect a redirect to /login
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function testHome() {
        $response = $this->get('/app');
        // expect a redirect to /login
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
}
