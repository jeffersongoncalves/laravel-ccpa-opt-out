<?php

return [

    'table' => 'ccpa_opt_outs',

    /*
     * Store IP address and user agent with each request, to show when and how the
     * consumer opted out (requests must be kept for 24 months, CCPA Regulations §7101).
     */
    'store_request' => true,

    /*
     * Treat a Global Privacy Control signal (Sec-GPC: 1) as a valid request to opt out of
     * sale and sharing (CCPA Regulations §7025). Applied by the `ccpa.gpc` middleware.
     */
    'honor_gpc' => true,

    /*
     * After an opt-out, wait this long before asking the consumer to opt back in
     * (Cal. Civ. Code §1798.135(c)(4)). The consumer can still opt in on their own.
     */
    'reask_after' => '12 months',

    /*
     * Optional endpoint for your "Do Not Sell or Share My Personal Information" and
     * "Limit the Use of My Sensitive Personal Information" links: POST {right, opted_out}.
     * Visitors are identified by an encrypted cookie holding a random id.
     */
    'route' => [
        'enabled' => false,
        'path' => 'ccpa/opt-out',
        'middleware' => ['web', 'throttle:60,1'],
        'cookie' => 'ccpa_opt_out_id',
        // On login, copy the visitor's latest choices (by cookie id) onto the user.
        'claim_on_login' => true,
    ],

];
