<?php

/*
 * Copyright (c) 2017, 2018 François Kooman <fkooman@tuxed.net>
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

namespace fkooman\OAuth\Client\Http;

class Request
{
    /** @var string */
    private $requestMethod;

    /** @var string */
    private $requestUri;

    /** @var string|null */
    private $requestBody;

    /** @var array<string,string> */
    private $requestHeaders;

    /**
     * @param string      $requestMethod
     * @param string      $requestUri
     * @param array       $requestHeaders
     * @param string|null $requestBody
     */
    public function __construct($requestMethod, $requestUri, array $requestHeaders = [], $requestBody = null)
    {
        $this->requestMethod = $requestMethod;
        $this->requestUri = $requestUri;
        $this->requestBody = $requestBody;
        $this->requestHeaders = $requestHeaders;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $requestHeaders = [];
        foreach ($this->requestHeaders as $k => $v) {
            // we do NOT want to log HTTP Basic credentials
            if ('Authorization' === $k) {
                if (0 === \strpos($v, 'Basic ')) {
                    $v = 'XXX-REPLACED-FOR-LOG-XXX';
                }
            }
            $requestHeaders[] = \sprintf('%s: %s', $k, $v);
        }

        return \sprintf(
            '[requestMethod=%s, requestUri=%s, requestHeaders=[%s], requestBody=%s]',
            $this->requestMethod,
            $this->requestUri,
            \implode(', ', $requestHeaders),
            $this->requestBody
        );
    }

    /**
     * @param string $requestUri
     * @param array  $requestHeaders
     *
     * @return Request
     */
    public static function get($requestUri, array $requestHeaders = [])
    {
        return new self('GET', $requestUri, $requestHeaders);
    }

    /**
     * @param string $requestUri
     * @param array  $postData
     * @param array  $requestHeaders
     *
     * @return Request
     */
    public static function post($requestUri, array $postData = [], array $requestHeaders = [])
    {
        return new self(
            'POST',
            $requestUri,
            \array_merge(
                $requestHeaders,
                ['Content-Type' => 'application/x-www-form-urlencoded']
            ),
            \http_build_query($postData, '&')
        );
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function setHeader($key, $value)
    {
        $this->requestHeaders[$key] = $value;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->requestMethod;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->requestUri;
    }

    /**
     * @return string|null
     */
    public function getBody()
    {
        return $this->requestBody;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->requestHeaders;
    }
}
