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

use fkooman\YubiTwee\HttpClientInterface;

class MockHttpClient implements HttpClientInterface
{
    /** @var string */
    private $testName;

    public function __construct($testName)
    {
        $this->testName = $testName;
    }

    public function get(array $uriList)
    {
        switch ($this->testName) {
            case 'testOkay':
                return <<< EOT
h=KXndnUnPk2n+APc1wQjs15HnKNs=
t=2017-01-02T14:03:28Z0405
otp=vvbvdirtrlvddetcvnndcufrjdjukelgfrtfnnfbijui
nonce=602bbcad5b9f4790b591cd356a8f9a2b
sl=25
status=OK
EOT;
                break;
            case 'testReplayedOtp':
                return <<< EOT
h=xdgF8F+qgwDmxFoigpylrGYBcHM=
t=2017-01-02T14:07:25Z0530
otp=vvbvdirtrlvddetcvnndcufrjdjukelgfrtfnnfbijui
nonce=602bbcad5b9f4790b591cd356a8f9a2c
status=REPLAYED_OTP
EOT;
                break;
            case 'testReplayedRequest':
                return <<< EOT
h=ExU/cCnuG+6NGu4VHaz+DuFOB3k=
t=2017-01-02T14:05:16Z0503
otp=vvbvdirtrlvddetcvnndcufrjdjukelgfrtfnnfbijui
nonce=602bbcad5b9f4790b591cd356a8f9a2b
status=REPLAYED_REQUEST
EOT;
                break;
            case 'testSignedOkay':
                return <<< EOT
h=wcaJgRzRG/nmdCEA3Jlkq0evpLQ=
t=2017-01-02T14:12:12Z0197
otp=vvbvdirtrlvdvvlfjurvlbudndrugkjukgvuhufuhfnt
nonce=c49d1205699edc3ff19c12b1f5efbcfb
sl=25
timestamp=13230820
sessioncounter=4
sessionuse=4
status=OK
EOT;
                break;
            default:
                return '';
        }
    }
}
