<div class="wrap">
    <h1>Post SMTP Email Logs</h1>
    <input type="hidden" id="ps-email-log-nonce" />
    <table width="100%" id="ps-email-log">
        <thead>
            <tr>
                <th><input type="checkbox" /></th>
                <th>Subject</th>
                <th>Sent To</th>
                <th>Status</th>
                <th>Solution</th>
                <th>Delivery Time</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th><input type="checkbox" /></th>
                <th>Subject</th>
                <th>Sent To</th>
                <th>Status</th>
                <th>Solution</th>
                <th>Delivery Time</th>
            </tr>
        </tfoot>
    </table>
</div>
<?php

// if( !class_exists( 'PostmanEmailLogTable' ) ):
// class PostmanEmailLogTable {

//     private static $_instance = null;


//     /**
//      * Single-ton Class Starter
//      * 
//      * @since 2.5.0
//      * @version 1.0.0
//      */
//     public static function get_instance() {

//         if( self::$_instance === null ) {

//             self::$_instance = new self();

//         }

//         return self::$_instance;

//     }


//     /**
//      * PostmanEmailLogTable
//      * 
//      * @since 2.5.0
//      * @version 1.0.0
//      */
//     public function __construct() {

//         echo 'asd';die;
        
//     }

// }

// PostmanEmailLogTable::get_instance();

// endif;