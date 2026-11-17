<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\DuskTestCase;

class LoginTest extends DuskTestCase
{
    /**
     * Skenario pengujian login sukses sebagai admin WarisAlam.
     *
     * @group Login
     */
    #[Group('Login')]
    public function testLogin(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            $browser->visit('/admin/login')
                ->waitForText('Sign in')
                ->type('input[type="email"]', 'admin@warisalam.test')
                ->type('input[type="password"]', 'password')
                ->press('Sign in')
                ->waitForLocation('/admin', 10)
                ->assertPathIs('/admin')
                ->assertSee('WarisAlam');
        });
    }

    /**
     * Skenario pengujian kegagalan login untuk akun nonaktif (PBI #04).
     *
     * @group InactiveLogin
     */
    #[Group('InactiveLogin')]
    public function testInactiveUserCannotLogin(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->driver->manage()->deleteAllCookies();

            $browser->visit('/admin/login')
                ->waitForText('Sign in')
                ->type('input[type="email"]', 'usertest@warisalam.test')
                ->type('input[type="password"]', 'password')
                ->press('Sign in')
                ->pause(2000)
                ->assertPathIs('/admin/login');
        });
    }

    /**
     * Skenario pengujian admin mengelola akun dan yurisdiksi wilayah (PBI #04, #05, #06).
     *
     * @group UserManagement
     */
    #[Group('UserManagement')]
    public function testAdminCanAccessUserManagement(): void
    {
        $admin = User::where('email', 'admin@warisalam.test')->first();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/users')
                ->waitForText('Tata Kelola Akun', 10)
                ->assertSee('Tata Kelola Akun');
        });
    }
}
