<?php

namespace PostSMTP\Vendor;

use PostSMTP\Vendor\ParagonIE\ConstantTime\Base32;
use PostSMTP\Vendor\ParagonIE\ConstantTime\Base32Hex;
use PostSMTP\Vendor\ParagonIE\ConstantTime\Base64;
use PostSMTP\Vendor\ParagonIE\ConstantTime\Base64UrlSafe;
use PostSMTP\Vendor\ParagonIE\ConstantTime\Base64DotSlash;
use PostSMTP\Vendor\ParagonIE\ConstantTime\Base64DotSlashOrdered;
use PostSMTP\Vendor\ParagonIE\ConstantTime\EncoderInterface;
use PostSMTP\Vendor\ParagonIE\ConstantTime\Hex;
require __DIR__ . '/../vendor/autoload.php';
/** @var PhpFuzzer\Config $config */
$config->setTarget(function (string $input) {
    static $targets = [Base32::class, Base32Hex::class, Base64::class, Base64UrlSafe::class, Base64DotSlash::class, Base64DotSlashOrdered::class, Hex::class];
    /** @var class-string<EncoderInterface> $target */
    $target = $targets[\array_rand($targets)];
    $out = $target::decode($input, \true);
    $re = $target::encode($out);
    if ($re !== $input) {
        throw new \Exception('invalid');
    }
});
