<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\Browser;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerFilamentBrowserMacros();
    }

    /**
     * Register custom browser macros for Filament components.
     */
    protected function registerFilamentBrowserMacros(): void
    {
        if (Browser::hasMacro('filamentSelect')) {
            return;
        }

        /**
         * Select an option from a Filament select dropdown.
         * Handles both Choices.js (searchable) and native HTML selects.
         *
         * Usage: $browser->filamentSelect('department', 'Security')
         */
        Browser::macro('filamentSelect', function (string $field, string $option) {
            /** @var Browser $this */

            // Try to find the container by wire:key
            $result = $this->script("
                const container = document.querySelector('[wire\\\\:key*=\"{$field}\"]');
                if (!container) return { found: false, type: null };

                // Check if it's a Choices.js select (has .choices class)
                const choicesEl = container.querySelector('.choices');
                if (choicesEl) return { found: true, type: 'choices' };

                // Check if it's a native select
                const selectEl = container.querySelector('select');
                if (selectEl) return { found: true, type: 'native' };

                // Check if it's Filament's custom select (fi-fo-select)
                const fiSelect = container.querySelector('.fi-fo-select');
                if (fiSelect) return { found: true, type: 'filament' };

                return { found: true, type: 'unknown' };
            ");

            $selectInfo = $result[0] ?? ['found' => false, 'type' => null];

            if ($selectInfo['type'] === 'choices') {
                // Choices.js select (searchable selects)
                $this->script("
                    const container = document.querySelector('[wire\\\\:key*=\"{$field}\"]');
                    if (container) {
                        container.querySelector('.choices').click();
                    }
                ");

                $this->pause(500);

                $this->script("
                    const container = document.querySelector('[wire\\\\:key*=\"{$field}\"]');
                    if (container) {
                        const options = container.querySelectorAll('.choices__list--dropdown .choices__item--selectable');
                        for (const opt of options) {
                            if (opt.textContent.trim() === '{$option}') {
                                opt.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
                                break;
                            }
                        }
                    }
                ");
            } elseif ($selectInfo['type'] === 'native') {
                // Native HTML select
                $this->script("
                    const container = document.querySelector('[wire\\\\:key*=\"{$field}\"]');
                    if (container) {
                        const select = container.querySelector('select');
                        if (select) {
                            const options = select.querySelectorAll('option');
                            for (const opt of options) {
                                if (opt.textContent.trim() === '{$option}') {
                                    select.value = opt.value;
                                    select.dispatchEvent(new Event('change', { bubbles: true }));
                                    break;
                                }
                            }
                        }
                    }
                ");
            } else {
                // Filament's custom select or unknown - try clicking to open dropdown
                $this->script("
                    const container = document.querySelector('[wire\\\\:key*=\"{$field}\"]');
                    if (container) {
                        const button = container.querySelector('button, [role=\"listbox\"], .fi-select-button');
                        if (button) button.click();
                    }
                ");

                $this->pause(500);

                $this->script("
                    const listbox = document.querySelector('[role=\"listbox\"]');
                    if (listbox) {
                        const options = listbox.querySelectorAll('[role=\"option\"]');
                        for (const opt of options) {
                            if (opt.textContent.trim() === '{$option}') {
                                opt.click();
                                break;
                            }
                        }
                    }
                ");
            }

            $this->pause(300);

            return $this;
        });
    }

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515', '--whitelisted-ips=']);
        }
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
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--ignore-certificate-errors',
            '--ignore-ssl-errors',
            '--allow-insecure-localhost',
            '--disable-extensions',
            '--disable-setuid-sandbox',
            '--remote-debugging-port=9222',
            '--disable-software-rasterizer',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        $options->setExperimentalOption('excludeSwitches', ['enable-automation']);
        $options->setExperimentalOption('useAutomationExtension', false);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        $capabilities->setCapability('acceptInsecureCerts', true);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            $capabilities,
            60000, // Connection timeout in ms
            60000  // Request timeout in ms
        );
    }
}
