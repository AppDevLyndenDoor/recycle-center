<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use Tests\Browser\Pages\MainLayout;


class LoginTest extends DuskTestCase
{

    /**
     * Test user can login/logout.
     *
     * @return void
     */
    public function testUserCanLogin()
    {
        $this->browse(function (Browser $browser) {
            $browser
            ->visit(new MainLayout)
            // ->pause(50000)
            ->waitFor('#dark',1)
            ->click('#dark')
            ->click('#sharedAccount')
            // Pass Credentials to function in MainLayout.php
            ->submitForm([
                'email' => 'scott.anderson@lyndendoor.com',
                'password' => '1234',
            ])
            // ->pause(5000)
            ->waitFor('#welcome',1)
            ->assertPathIs('/dashboard')
            ->click('#tl_logout')
            ->waitFor('#tl_login', 1)
            ->assertPathIs('/')
            ;
        });
    }

    // Common Utiliities or actions
    // ->pause(5000)
    // ->click('#selector')
    // ->assertPathIs('/path')
    // ->type('words')
    // ->waitUntilMissing('#selector')
    // ->waitFor('#selector')
};
