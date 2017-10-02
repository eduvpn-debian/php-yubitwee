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

use fkooman\YubiTwee\Exception\YubiTweeException;
use ParagonIE\ConstantTime\Base64;

class Validator
{
    /** @var string[] */
    private $hostList = [
        'api.yubico.com',
        'api2.yubico.com',
        'api3.yubico.com',
        'api4.yubico.com',
        'api5.yubico.com',
    ];

    /** @var HttpClientInterface */
    private $httpClient;

    /** @var RandomInterface */
    private $random;

    /** @var string|null */
    private $clientId = null;

    /** @var string|null */
    private $clientSecret = null;

    public function __construct(HttpClientInterface $httpClient, RandomInterface $random = null)
    {
        $this->httpClient = $httpClient;
        if (null === $random) {
            $random = new Random();
        }
        $this->random = $random;
    }

    /**
     * @param string $clientId
     *
     * @return void
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @param string $clientSecret
     *
     * @return void
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @param string[] $hostList
     *
     * @return void
     */
    public function setHosts(array $hostList)
    {
        $this->hostList = $hostList;
    }

    /**
     * @param string $otp
     *
     * @return \fkooman\YubiTwee\ValidatorResponse
     */
    public function verify($otp)
    {
        self::verifyOtp($otp);

        $requestNonce = $this->random->getNonce();
        $requestParameters = $this->prepareRequestParameters($requestNonce, $otp);

        $uriList = [];
        foreach ($this->hostList as $apiHost) {
            $uriList[] = sprintf(
                'https://%s/wsapi/2.0/verify?%s',
                $apiHost,
                $requestParameters
            );
        }

        // obtain response body
        $responseBody = $this->httpClient->get($uriList);

        // verify the response
        return $this->verifyResponseBody($responseBody, $requestNonce, $otp);
    }

    /**
     * @param string $otp
     *
     * @return void
     */
    private static function verifyOtp($otp)
    {
        if (1 !== preg_match('/^[[:print:]]{32,48}$/', $otp)) {
            throw new YubiTweeException('invalid YubiKey OTP format');
        }
    }

    /**
     * @param string $requestNonce
     * @param string $otp
     *
     * @return string
     */
    private function prepareRequestParameters($requestNonce, $otp)
    {
        $queryParameters = [
            'id' => null === $this->clientId ? 1 : $this->clientId,
            'nonce' => $requestNonce,
            'otp' => $otp,
            'timestamp' => '1',
        ];

        // when using HTTPS it is not needed to sign the request, but we support
        // it anyway if the API consumer wants it
        if (null !== $this->clientSecret) {
            $queryParameters['h'] = $this->generateHash($queryParameters);
        }

        return http_build_query($queryParameters, '', '&');
    }

    /**
     * @param string $responseBody
     * @param string $requestNonce
     * @param string $otp
     *
     * @return \fkooman\YubiTwee\ValidatorResponse
     */
    private function verifyResponseBody($responseBody, $requestNonce, $otp)
    {
        $keyValue = self::parseResponseBody($responseBody);

        // we need to catch this status first, as the signature verification
        // will fail as well and then hide the actual problem from the caller
        if ('BAD_SIGNATURE' === $keyValue['status']) {
            throw new YubiTweeException('invalid request signature');
        }

        // when using HTTPS it is not needed to verify the HMAC, but we support
        // it anyway if a clientId/clientSecret are set
        if (null !== $this->clientSecret) {
            $this->verifySignature($keyValue);
        }

        if (array_key_exists('nonce', $keyValue)) {
            if ($requestNonce !== $keyValue['nonce']) {
                throw new YubiTweeException('unexpected nonce value in response body');
            }
        }

        if (array_key_exists('otp', $keyValue)) {
            if ($otp !== $keyValue['otp']) {
                throw new YubiTweeException('unexpected OTP value in response body');
            }
        }

        return new ValidatorResponse($keyValue);
    }

    /**
     * @param array $keyValue
     *
     * @return string
     */
    private function generateHash(array $keyValue)
    {
        ksort($keyValue);
        // the "plaintext" needs to not be URL encoded
        $plainText = urldecode(
            http_build_query($keyValue, '', '&')
        );

        return Base64::encode(
            hash_hmac(
                'sha1',
                $plainText,
                Base64::decode(
                    $this->clientSecret
                ),
                true
            )
        );
    }

    /**
     * @param string $responseBody
     *
     * @return array
     */
    private static function parseResponseBody($responseBody)
    {
        if (!is_string($responseBody)) {
            throw new YubiTweeException('the response body from HTTP client MUST be a string');
        }
        if (false === strpos($responseBody, "\n")) {
            throw new YubiTweeException('the response body from HTTP client MUST contain newlines');
        }

        $keyValue = [];
        $responseRows = explode("\n", $responseBody);
        foreach ($responseRows as $responseRow) {
            if (false !== strpos($responseRow, '=')) {
                list($k, $v) = explode('=', $responseRow, 2);
                $keyValue[$k] = trim($v);
            }
        }

        // we always MUST have 'h'
        if (!array_key_exists('h', $keyValue)) {
            throw new YubiTweeException('missing "h" key in the response body');
        }

        // and also MUST have 'status'
        if (!array_key_exists('status', $keyValue)) {
            throw new YubiTweeException('missing "status" key in the response body');
        }

        return $keyValue;
    }

    /**
     * @param array $keyValue
     *
     * @return void
     */
    private function verifySignature(array $keyValue)
    {
        $serverHash = $keyValue['h'];
        unset($keyValue['h']);

        $ourHash = $this->generateHash($keyValue);

        if (false === hash_equals($ourHash, $serverHash)) {
            throw new YubiTweeException('response signature does not match with expected value');
        }
    }
}
