<div class="wrap">
    <h1>Post SMTP Email Logs</h1>
    <input type="hidden" id="ps-email-log-nonce" value="<?php echo wp_create_nonce( 'security' ) ?>" />
    <table width="100%" id="ps-email-log">
        <thead>
            <tr>
                <th><input type="checkbox" class="ps-email-log-select-all" /></th>
                <th>Subject</th>
                <th>Sent To</th>
                <th>Status</th>
                <th>Solution</th>
                <th>Delivery Time</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th><input type="checkbox" class="ps-email-log-select-all" /></th>
                <th>Subject</th>
                <th>Sent To</th>
                <th>Status</th>
                <th>Solution</th>
                <th>Delivery Time</th>
            </tr>
        </tfoot>
    </table>
</div>