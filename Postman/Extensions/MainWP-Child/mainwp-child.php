<?php

require_once 'includes/psmwp-init.php';

/**
 * Load Post SMTP for MainWP Child
 * 
 * @since 2.6.0
 * @version 2.6.0
 */
function load_post_smtp_for_mainwp_child() {
    
    Post_SMTP_MainWP_Child::get_instance();
    
}

load_post_smtp_for_mainwp_child();