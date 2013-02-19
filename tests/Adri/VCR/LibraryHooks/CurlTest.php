<?php

namespace Adri\VCR\LibraryHooks;

use Adri\VCR\Response;

/**
 * Test if intercepting http/https using stream wrapper works.
 */
class CurlTest extends \PHPUnit_Framework_TestCase
{
    public $expected = 'example response body';

    public function testShouldInterceptCallWhenEnabled()
    {
        $curlHook = $this->createCurl();
        $curlHook->enable();

        $ch = curl_init("http://google.com/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $actual = curl_exec($ch);
        curl_close($ch);

        $curlHook->disable();
        $this->assertEquals($this->expected, $actual, 'Response was not returned.');
    }

    public function testShouldNotInterceptCallWhenNotEnabled()
    {
        $testClass = $this;
        $curlHook = $this->createCurl(function($request) use($testClass) {
            $testClass->fail("This request should not have been intercepted.");
        });

        $ch = curl_init("https://google.com/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    public function testShouldNotInterceptCallWhenDisabled()
    {
        $testClass = $this;
        $curlHook = $this->createCurl(function($request) use($testClass) {
            $testClass->fail("This request should not have been intercepted.");
        });
        $curlHook->enable();
        $curlHook->disable();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://google.com/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    public function testShouldWriteFileOnFileDownload()
    {
        $curlHook = $this->createCurl();
        $curlHook->enable();
        file_put_contents('tests/fixtures/test', '');

        $ch = curl_init("https://google.com/");
        curl_setopt($ch, CURLOPT_FILE, fopen('tests/fixtures/test', 'w'));
        curl_exec($ch);
        curl_close($ch);

        $curlHook->disable();
        $this->assertEquals($this->expected, file_get_contents('tests/fixtures/test'), 'Response was not written in file.');
        file_put_contents('tests/fixtures/test', '');
    }

    public function testShouldEchoResponseIfReturnTransferFalse()
    {
        $curlHook = $this->createCurl();
        $curlHook->enable();

        $ch = curl_init("http://google.com/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        ob_start();
        curl_exec($ch);
        $actual = ob_get_contents();
        ob_end_clean();
        curl_close($ch);

        $curlHook->disable();
        $this->assertEquals($this->expected, $actual, 'Response was not written on stdout.');
    }

    public function testShouldNotThrowErrorWhenDisabledTwice()
    {
        $curlHook = $this->createCurl();
        $curlHook->disable();
        $curlHook->disable();
    }

    public function testShouldNotThrowErrorWhenEnabledTwice()
    {
        $curlHook = $this->createCurl();
        $curlHook->enable();
        $curlHook->enable();
    }

    /**
     * @return \Adri\VCR\LibraryHooks\Curl
     */
    private function createCurl($handleRequestCallback = null)
    {
        if (is_null($handleRequestCallback)) {
            $testClass = $this;
            $handleRequestCallback = function($request) use($testClass) {
                return new Response(200, null, $testClass->expected);
            };
        }
        return new Curl($handleRequestCallback);
    }
}
