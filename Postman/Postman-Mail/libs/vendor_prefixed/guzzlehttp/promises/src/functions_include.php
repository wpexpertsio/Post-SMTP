<?php

namespace PostSMTP\Vendor;

// Don't redefine the functions if included multiple times.
if (!\function_exists('PostSMTP\\Vendor\\GuzzleHttp\\Promise\\promise_for')) {
    require __DIR__ . '/functions.php';
}
