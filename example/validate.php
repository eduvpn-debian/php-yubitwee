<?php
/**
 * Copyright (c) 2017 François Kooman <fkooman@tuxed.net>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
require_once sprintf('%s/vendor/autoload.php', dirname(__DIR__));

use fkooman\YubiTwee\CurlMultiHttpClient;
use fkooman\YubiTwee\Exception\YubiTweeException;
use fkooman\YubiTwee\Validator;

if (2 > $argc) {
    echo sprintf('Syntax: %s OTP', $argv[0]).PHP_EOL;
    exit(1);
}

// verify the YubiKey OTP
try {
    $v = new Validator(
        new CurlMultiHttpClient()
    );

    // OPTIONALLY obtain API credentials: https://upgrade.yubico.com/getapikey/
    // this will in addition also sign the requests, and verify the responses
    //$v->setClientId('12345');
    //$v->setClientSecret('XYZABC');

    $response = $v->verify($argv[1]);

    if ($response->success()) {
        echo 'OK'.PHP_EOL;
        // get the ID of the YubiKey to bind to a user account
        echo 'ID: '.$response->id().PHP_EOL;
    } else {
        echo 'FAILED'.PHP_EOL;
        echo 'ERROR: '.$response->status().PHP_EOL;
    }
} catch (YubiTweeException $e) {
    echo 'ERROR: '.$e->getMessage().PHP_EOL;
    exit(1);
}
