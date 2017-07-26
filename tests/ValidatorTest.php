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

namespace fkooman\YubiTwee\Tests;

use fkooman\YubiTwee\Validator;
use PHPUnit_Framework_TestCase;

class ValidatorTest extends PHPUnit_Framework_TestCase
{
    public function testOkay()
    {
        $v = new Validator(new TestHttpClient('testOkay'), new TestRandom('602bbcad5b9f4790b591cd356a8f9a2b'));
        $response = $v->verify('vvbvdirtrlvddetcvnndcufrjdjukelgfrtfnnfbijui');
        $this->assertTrue($response->success());
        $this->assertSame('vvbvdirtrlvd', $response->id());
        $this->assertSame('2017-01-02 14:03:28', $response->timestamp()->format('Y-m-d H:i:s'));
    }

    public function testReplayedRequest()
    {
        $v = new Validator(new TestHttpClient('testReplayedRequest'), new TestRandom('602bbcad5b9f4790b591cd356a8f9a2b'));
        $response = $v->verify('vvbvdirtrlvddetcvnndcufrjdjukelgfrtfnnfbijui');
        $this->assertFalse($response->success());
        $this->assertSame('REPLAYED_REQUEST', $response->status());
    }

    public function testReplayedOtp()
    {
        $v = new Validator(new TestHttpClient('testReplayedOtp'), new TestRandom('602bbcad5b9f4790b591cd356a8f9a2c'));
        $response = $v->verify('vvbvdirtrlvddetcvnndcufrjdjukelgfrtfnnfbijui');
        $this->assertFalse($response->success());
        $this->assertSame('REPLAYED_OTP', $response->status());
    }

    public function testSignedOkay()
    {
        $v = new Validator(new TestHttpClient('testSignedOkay'), new TestRandom('c49d1205699edc3ff19c12b1f5efbcfb'));
        $v->setClientId('30972');
        $v->setClientSecret('wbEmh4faKEIOr6ro4wrgaAhskxc=');
        $response = $v->verify('vvbvdirtrlvdvvlfjurvlbudndrugkjukgvuhufuhfnt');
        $this->assertTrue($response->success());
        $this->assertSame('vvbvdirtrlvd', $response->id());
    }
}
