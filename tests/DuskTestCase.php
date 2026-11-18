<?php

namespace Tests;

use Closure;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
            static::waitForChromeDriver();
        }
    }

    /**
     * ChromeDriver needs a moment to start on Windows. Wait until its port
     * accepts connections so the first test does not fail.
     */
    protected static function waitForChromeDriver(int $seconds = 10): void
    {
        $deadline = microtime(true) + $seconds;

        while (microtime(true) < $deadline) {
            $socket = @fsockopen('127.0.0.1', 9515, $errno, $errstr, 0.5);

            if ($socket) {
                fclose($socket);

                return;
            }

            usleep(200_000);
        }
    }

    /**
     * Run the test, then save a screenshot of the final page as test evidence:
     * tests/Browser/screenshots/evidence/{TestClass}-{test_method}.png
     */
    public function browse(Closure $callback)
    {
        parent::browse(function (Browser $browser) use ($callback) {
            $callback($browser);

            // Let the map tiles finish loading so the screenshot is not grey or blurry.
            try {
                $browser->waitUntil(
                    '!document.querySelector(".leaflet-tile-container img:not(.leaflet-tile-loaded)") && !document.querySelector(".leaflet-zoom-anim")',
                    5,
                )->pause(300);
            } catch (TimeoutException) {
                // Offline tiles should not fail the test; take the screenshot anyway.
            }

            File::ensureDirectoryExists(Browser::$storeScreenshotsAt.'/evidence');
            $browser->screenshot('evidence/'.class_basename(static::class).'-'.$this->name());
        });
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
