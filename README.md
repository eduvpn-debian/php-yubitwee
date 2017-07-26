[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/fkooman/php-yubitwee/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/fkooman/php-yubitwee/?branch=master)

A very simple, secure [YubiKey](https://yubico.com/) OTP Validator with 
pluggable HTTP client.

**NOTE**: the correct thing to be doing is using U2F, so do not use this 
library if you are writing new software, please investigate U2F as that will 
be more secure than using the proprietary YubiCo solution for verifying 
hardware tokens!

# Why

This library is designed to be as simple and secure as possible, existing
libraries, e.g. `enygma/yubikey` and `SURFnet/yubikey-api-client` were lacking
in one or more of the features below:

- custom HTTP client support (cURL, Guzzle, ...);
  - `CurlHttpClient` and `CurlMultiHttpClient` included;
- only uses HTTPS;
- support custom servers;
- using [pecl-libsodium](https://paragonie.com/book/pecl-libsodium) for CSPRNG;
- easy access to exact status code from server;
- make YubiKey ID available to the API consumer (for binding to user accounts);
- OPTIONALLY support YubiCo API keys for signing and signature validation in
  response from YubiCo servers;
- supports parallel requests and taking the first response to come in (with
  `CurlMultiHttpClient`)

# Using

For this library you do NOT need to obtain YubiCo API keys as HTTPS is used 
together with the 
[2.0 Protocol](https://developers.yubico.com/yubikey-val/Validation_Protocol_V2.0.html).

# Integrating

Make sure you read the 
[documentation](https://developers.yubico.com/OTP/Libraries/Using_a_library.html) 
on how to use a library for effective two-factor authentication in your 
application.

# API

See the `example/validate.php` script for an example on how to use the library.

# Overriding Servers

    $v->setHosts(['api.yubico.com', 'api2.yubico.com']);

# Error Handling

In the "normal" scenario, you only have to deal with the `success()` call to 
determine if the OTP was valid or not. The `status()` call can be used to 
get the actual status received from the server. The `status()` call will 
inform you what the exact error is, e.g. in case of OTP replay or when the OTP 
is invalid.

If any (unrecoverable) error occurs, the `YubiTweeException` is thrown, that 
could be for example wrong API keys that cause wrong signatures.

# Other HTTP libraries

Below is an example for using Guzzle (5.3) with this library instead of the 
built-in cURL handler, it is not included because that would introduce a 
dependency on Guzzle in this library, and it is very simple, i.e. it does not 
support parallel requests:

    class GuzzleHttpClient implements HttpClientInterface
    {
        /** @var \GuzzleHttp\Client */
        private $client;

        public function __construct(Client $client)
        {
            $this->client = $client;
        }

        public function get(array $apiUriList)
        {
            $apiUri = $apiUriList[random_int(0, count($apiUriList) - 1)];

            return (string) $this->client->get($apiUri)->getBody();
        }
    }

# License

[MIT](LICENSE).
