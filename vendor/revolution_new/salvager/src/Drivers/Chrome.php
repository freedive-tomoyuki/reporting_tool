<?php

namespace Revolution\Salvager\Drivers;

use Closure;
use Laravel\Dusk\Chrome\SupportsChrome;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

use Revolution\Salvager\Contracts\Driver;

class Chrome implements Driver
{
    use SupportsChrome;

    /**
     * @var array
     */
    private $options;

    /**
     * Chrome constructor.
     *
     * @param array|null $options
     */
    public function __construct(array $options = null)
    {
        $this->options = $options ?? [
                '--window-size=1920,3000',
                '--start-maximized',
                '--headless',
                '--disable-gpu',
                '--no-sandbox',
            ];
    }

    /**
     * @return RemoteWebDriver
     */
    public function create()
    {
        $options = (new ChromeOptions)->addArguments($this->options);

        return RemoteWebDriver::create(
            'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
            ChromeOptions::CAPABILITY, $options
        )->setCapability('acceptInsecureCerts', TRUE)
        );
    }

    /**
     * @return void
     */
    public function start()
    {
        static::startChromeDriver();
    }

    /**
     * @return void
     */
    public function stop()
    {
        static::stopChromeDriver();
    }

    /**
     * @return array
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * @param Closure $callback
     *
     * @return void
     */
    public static function afterClass(Closure $callback)
    {
        //
    }

    /**
     * @return void
     */
    public function __destruct()
    {
        $this->stop();
    }
}
