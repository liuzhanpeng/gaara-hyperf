# Authenticators

> 中文文档请查看 [authenticators.md](authenticators.md)

An authenticator is the core of the authentication flow. When a request reaches a guard, the guard calls each authenticator's `supports()` method in order. The first authenticator that returns `true` is responsible for completing authentication.

---

## Form Login Authenticator (form_login)

Suitable for traditional web applications. Supports HTML form submissions, CSRF protection, and login success/failure redirects.

```php
'form_login' => [
    'check_path'         => '/admin/login',       // required: the path that accepts the POST form submission
    'target_path'        => '/admin/dashboard',   // optional: redirect path after successful login
    'failure_path'       => '/admin/login',        // optional: redirect path after failed login
    'redirect_enabled'   => true,                 // optional: allow ?redirect_to= to override target_path
    'redirect_field'     => 'redirect_to',         // optional: redirect query parameter name
    'username_field'     => 'username',            // optional: form username field name
    'password_field'     => 'password',            // optional: form password field name
    'error_message'      => 'Invalid username or password',
    'csrf_enabled'       => true,                  // optional: enable CSRF protection
    'csrf_id'            => 'authenticate',         // optional: CSRF token ID
    'csrf_field'         => '_csrf_token',          // optional: CSRF token form field name
    'csrf_token_manager' => 'default',              // optional: which CSRF manager to use
    'success_handler'    => null,                  // optional: custom success handler
    'failure_handler'    => null,                  // optional: custom failure handler
],
```

**How it works**:
1. Only triggers (`supports()`) for POST requests to `check_path`.
2. Reads the username and password from the request body.
3. Optionally validates the CSRF token (prevents cross-site request forgery).
4. Uses `UserProvider` to find the user, then `PasswordHasher` to verify the password.
5. On successful login, migrates the session to prevent session fixation attacks.

---

## JSON Login Authenticator (json_login)

Suitable for API login endpoints in a decoupled front-end/back-end architecture. Accepts a JSON request body.

```php
'json_login' => [
    'check_path'      => '/api/login',     // required: login endpoint path (POST)
    'username_field'  => 'username',       // optional: username field in the JSON body
    'password_field'  => 'password',       // optional: password field in the JSON body
    'error_message'   => 'Invalid username or password',
    'success_handler' => [
        'class'  => \GaaraHyperf\Authenticator\OpaqueTokenSuccessHandler::class,
        'params' => [
            'token_manager' => 'default',
        ],
    ],
    'failure_handler' => null,  // when not configured, returns a {"error": "..."} JSON response by default
],
```

`success_handler` is typically set to `OpaqueTokenSuccessHandler`. The response format is controlled by `services.opaque_token_managers.<name>.token_responder`.

---

## Opaque Token Authenticator (opaque_token)

Validates an opaque token (e.g. a Bearer token) carried in the request. Typically used together with `json_login`.

```php
'opaque_token' => [
    'token_manager'   => 'default',   // optional: which token manager to use (extraction method is configured in the manager)
    'success_handler' => null,
    'failure_handler' => null,
],
```

**Complete stateless API example** (`json_login` + `opaque_token`):

```php
'authenticators' => [
    'json_login' => [
        'check_path'      => '/api/login',
        'success_handler' => [
            'class'  => \GaaraHyperf\Authenticator\OpaqueTokenSuccessHandler::class,
            'params' => ['token_manager' => 'default'], // must match the token_manager in opaque_token
        ],
    ],
    'opaque_token' => [
        'token_manager' => 'default',
    ],
],
'token_storage' => ['type' => 'null'],
```

---

## API Key Authenticator (api_key)

Reads an API key from a request header. Suitable for service-to-service calls.

```php
'api_key' => [
    'api_key_field'   => 'X-API-KEY',  // optional: header name, default X-API-KEY
    'success_handler' => null,
    'failure_handler' => null,
],
```

The identifier passed to `UserProvider::findByIdentifier()` is the value from the API key header. The user model can implement API key lookup and validation there.

---

## HMAC Signature Authenticator (hmac)

For service-to-service calls. Verifies request integrity via a signature to prevent tampering and replay attacks.

```php
'hmac' => [
    'api_key_field'           => 'X-API-KEY',    // header: API key field name
    'signature_field'         => 'X-SIGNATURE',  // header: signature field name
    'timestamp_field'         => 'X-TIMESTAMP',  // header: timestamp field name (Unix timestamp)
    'nonce_enabled'           => true,            // enable nonce (replay protection)
    'nonce_field'             => 'X-NONCE',       // header: nonce field name
    'nonce_cache_prefix'      => 'hmac_nonce',    // nonce cache prefix (must be unique)
    'ttl'                     => 60,              // signature validity in seconds
    'leeway'                  => 300,             // timestamp tolerance in seconds, default 5 min
    'algo'                    => 'sha256',        // HMAC algorithm
    'secret_encrypto_enabled' => false,           // whether to encrypt the API secret in storage
    'secret_encryptor' => [                       // required when secret_encrypto_enabled == true
        'type' => 'default',
        'algo' => 'AES-256-CBC',
        'key'  => env('HMAC_ENCRYPTION_KEY'),
    ],
],
```

**Signing algorithm** (clients must generate signatures using the same rules):

```
queryString = URL-encoded query params sorted by key and concatenated
bodyHash    = SHA256(requestBody)

signData = METHOD + "\n"
         + PATH + "\n"
         + queryString + "\n"
         + apiKey + "\n"
         + timestamp
         + (nonce_enabled ? "\n" + nonce : "") + "\n"
         + bodyHash

signature = HMAC(algo, signData, apiSecret)
```

---

## X.509 Certificate Authenticator (x509)

Extracts the user identifier from the client certificate in a TLS mutual authentication (mTLS) setup.

```php
'x509' => [
    'ssl_client_s_dn_field' => 'SSL_CLIENT_S_DN',  // server parameter containing the certificate DN
    'identifier_field'      => 'cn',               // user identifier source: cn | email
],
```

Requires the web server (Nginx/Apache) to forward the client certificate Subject DN to PHP (via `$_SERVER` or a request attribute).

---

## Multiple Authenticators

A single guard can have multiple authenticators. They are checked in config order:

```php
'authenticators' => [
    // Order 1: check Bearer token first (already authenticated users)
    'opaque_token' => [],

    // Order 2: login endpoint
    'json_login' => [
        'check_path'      => '/api/login',
        'success_handler' => [...],
    ],

    // Order 3: service-to-service calls via API key
    'api_key' => [
        'api_key_field' => 'X-SERVICE-KEY',
    ],
],
```

---

## Custom Authenticator (custom)

```php
'authenticators' => [
    'custom' => [
        [
            'class'  => \App\Auth\WechatAuthenticator::class,
            'params' => ['app_id' => env('WECHAT_APP_ID')],
        ],
        [
            'class'  => \App\Auth\SmsCodeAuthenticator::class,
            'params' => [],
        ],
    ],
],
```

Custom authenticators must implement `GaaraHyperf\Authenticator\AuthenticatorInterface`, or extend `AbstractAuthenticator`. See [Extension Guide](extension.en.md) for details.
