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

use RuntimeException;

/**
 * HTTP cURL client connecting to all provided servers simultaneously and
 * returning the first (fastest) response.
 */
class CurlMultiHttpClient implements HttpClientInterface
{
    /** @var resource */
    private $curlMultiChannel;

    /** @var array */
    private $connectionList = [];

    public function __construct()
    {
        if (false === $curlMultiChannel = curl_multi_init()) {
            throw new RuntimeException('unable to create cURL multi channel');
        }
        $this->curlMultiChannel = $curlMultiChannel;
    }

    public function __destruct()
    {
        curl_multi_close($this->curlMultiChannel);
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $uriList)
    {
        foreach ($uriList as $apiUri) {
            $curlChannel = self::getCurlChannel($apiUri);
            curl_multi_add_handle($this->curlMultiChannel, $curlChannel);
            $this->connectionList[] = $curlChannel;
        }

        $responseData = $this->executeRequests();

        foreach ($this->connectionList as $curlChannel) {
            curl_multi_remove_handle($this->curlMultiChannel, $curlChannel);
            curl_close($curlChannel);
        }

        if (false === $responseData) {
            throw new RuntimeException('failure performing the HTTP requests');
        }

        return $responseData;
    }

    /**
     * @param string $apiUri
     *
     * @return resource
     */
    private static function getCurlChannel($apiUri)
    {
        if (false === $curlChannel = curl_init()) {
            throw new RuntimeException('unable to create cURL channel');
        }

        $curlOptions = [
            CURLOPT_URL => $apiUri,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => 0,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ];
        if (false === curl_setopt_array($curlChannel, $curlOptions)) {
            throw new RuntimeException('unable to set cURL options');
        }

        return $curlChannel;
    }

    /**
     * @return string|false
     */
    private function executeRequests()
    {
        $responseBody = false;
        $status = 0;
        do {
            $status = curl_multi_exec($this->curlMultiChannel, $active);
            if (false !== $info = curl_multi_info_read($this->curlMultiChannel)) {
                if (CURLE_OK === $info['result']) {
                    $responseBody = curl_multi_getcontent($info['handle']);
                }
            }
        } while (false === $responseBody && ($status === CURLM_CALL_MULTI_PERFORM || $active));

        return $responseBody;
    }
}
