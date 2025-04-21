<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;

class DashboardTest extends DuskTestCase
{
    public function test_dashboard_displays_jobs()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/jobs')
                ->assertSee('Background Job Dashboard')
                ->assertSee('View Logs');
        });
    }
}
