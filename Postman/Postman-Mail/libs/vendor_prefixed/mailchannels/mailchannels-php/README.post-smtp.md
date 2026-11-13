# Bundled MailChannels PHP SDK

Official package: `mailchannels/mailchannels-php`, version **2.2.0** (MIT).
Source: https://bitbucket.org/mailchannels/mailchannels-email-api-sdk-php/src/v2.2.0/
Commit: `61aef7877b5b38c3199a7a19cbe1bcea55df5538`.

`src/`, `LICENSE`, and `composer.json` come from that release. PHP namespace
tokens are prefixed with `PostSMTP\Vendor\`, matching the plugin's existing
bundled libraries. SDK logic, strings and comments are unchanged. Optional
Laravel/Symfony plugins are omitted. Do not edit the generated PHP files.

The engine explicitly supplies the existing prefixed Guzzle PSR-18 client and
PSR-17 factories. It also reuses the existing PSR interfaces and logger.
HTTP discovery is therefore never invoked and is not bundled. This avoids
another HTTP stack and leaves the existing Composer class map untouched.
The engine registers a separate SDK loader only on PHP 8.2+ when sending.
WordPress installations do not need Composer or a build step.

To reproduce from the repository root (PHP 8.2+ and Git):

```sh
git clone --branch v2.2.0 --depth 1 https://bitbucket.org/mailchannels/mailchannels-email-api-sdk-php.git /tmp/mailchannels-php
git -C /tmp/mailchannels-php rev-parse HEAD
php tools/vendor-mailchannels.php /tmp/mailchannels-php
php tests/mailchannels.php
git diff --exit-code -- Postman/Postman-Mail/libs/vendor_prefixed/mailchannels/
```

Confirm the source commit above before generating. When updating the SDK,
review upstream PHP/dependency requirements, remove any files deleted upstream,
regenerate, rerun the regression checks, and update this provenance record.
The vendor path is marked in `.gitattributes` to keep PR review focused on the
handwritten adapter and tests.
