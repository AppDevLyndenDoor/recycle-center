<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\Browser\Pages\MainLayout;
use Tests\DuskTestCase;
use function PHPSTORM_META\type;

class LoginTest extends DuskTestCase
{
    /**
     * Test user can login/logout.
     *
     * @return void
     */
    public function test_user_can_login()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->visit('/login')
                ->waitFor('#sharedAccount', 1)
                ->click('#sharedAccount')
                ->type('#email', 'scott.anderson@lyndendoor.com')
            ->type('#password', '1234')
                ->click('#Login')
            // ->pause(5000)
                ->waitFor('#mainLayout', 2)
                ->assertPathIs('/dashboard')
                ->click('#logout')
                ->waitFor('#sharedAccount', 2)
                ->assertPathIs('/');
        });
    }

    // Common Utiliities or actions
    // ->pause(5000)
    // ->click('#selector')
    // ->assertPathIs('/path')
    // ->type('words')
    // ->waitUntilMissing('#selector')
    // ->waitFor('#selector')
}
