<?php

namespace PostSMTP\Vendor;

// Don't redefine the functions if included multiple times.
if (!\function_exists('PostSMTP\\Vendor\\GuzzleHttp\\Psr7\\str')) {
    require __DIR__ . '/functions.php';
}
