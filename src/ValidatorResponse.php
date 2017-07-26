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

namespace fkooman\YubiTwee;

use DateTime;
use fkooman\YubiTwee\Exception\YubiTweeException;

class ValidatorResponse
{
    /** @var array */
    private $keyValue;

    public function __construct(array $keyValue)
    {
        $this->keyValue = $keyValue;
    }

    /**
     * @return bool
     */
    public function success()
    {
        return 'OK' === $this->keyValue['status'];
    }

    /**
     * @return string
     */
    public function status()
    {
        return $this->keyValue['status'];
    }

    /**
     * @return DateTime
     */
    public function timestamp()
    {
        // no idea what the last 4 digits mean in the "t" field...
        return new DateTime(substr($this->keyValue['t'], 0, -4));
    }

    /**
     * @return string
     */
    public function id()
    {
        if (32 === strlen($this->keyValue['otp'])) {
            throw new YubiTweeException('YubiKey OTP does not contain a unique identifier');
        }

        // return the last 32 characters of the string
        return substr($this->keyValue['otp'], 0, -32);
    }
}
