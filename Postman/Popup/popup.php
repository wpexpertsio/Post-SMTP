<div class="ps-pro-popup-overlay">
    <div class="ps-pro-popup-container">
        <div class="ps-pro-popup-outer">
            <div class="ps-pro-popup-body">
                <span class="dashicons dashicons-no-alt ps-pro-close-popup"></span>
                <div class="ps-pro-popup-content">
                    <img src="<?php echo  POST_SMTP_URL . '/Postman/Wizard/assets/images/wizard-google.png' ?>" class="ps-pro-for-img" />
                    <h1>Ready to Supercharge Your Emails with <span class="ps-pro-for">Google Mailer Setup?</span></h1>
                    <h4>Unlock this <strong>Pro Feature NOW</strong> and get a </h4> 
                    <span class="smily">🤩 <strong>HUGE 25% discount! </strong>🤩</span>
                    <div <?php echo postman_is_bfcm() ? 'style="background: url( '.esc_url( POST_SMTP_ASSETS . 'images/bfcm-2024/popup.png' ).' ); background-size: cover; margin: 20px 0 5px 0; padding: 16px 0px; position: relative;"' : 'class="ps-pro-promo-area"'; ?>>   
                        <?php /*
                        if( postman_is_bfcm() ) {
                            ?>
                            <p style="color: #fff; font-size: 14px; margin: 0 auto;">
                                <b style="color: #fbb81f;">24% OFF!</b> BFCM is here - Grab your deal before it's gone!🛍️
                            </p>
                            <?php
                        }
                        else {
                            ?>
                            <p>
                                <b>Bonus:</b> Upgrade now and get <span class="ps-pro-discount">25% off</span> on Post SMTP lifetime plans!
                            </p>
                            <?php
                        } */
                        ?>
                        <div <?php echo postman_is_bfcm() ? 'style="background: #fbb81f";' : '';  ?> class="ps-pro-coupon">
                            <b <?php echo postman_is_bfcm() ? 'style="color: #1a3b63";' : '';  ?>>
                                Use Coupon: <span class="ps-pro-coupon-code"><?php echo postman_is_bfcm() ? 'BFCM2024' : 'GETSMTPPRO'; ?></span> 
                                <span class="copy-icon ps-click-to-copy">
                                    <svg width="7" height="7" viewBox="0 0 7 7" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M6.1261 6.7273H3.47361C3.25716 6.7273 3.04957 6.64131 2.89651 6.48825C2.74345 6.3352 2.65746 6.12761 2.65746 5.91115V3.25867C2.65746 3.04221 2.74345 2.83462 2.89651 2.68156C3.04957 2.5285 3.25716 2.44252 3.47361 2.44252H6.1261C6.34255 2.44252 6.55015 2.5285 6.7032 2.68156C6.85626 2.83462 6.94225 3.04221 6.94225 3.25867V5.91115C6.94165 6.12742 6.85547 6.33466 6.70254 6.48759C6.54961 6.64052 6.34237 6.7267 6.1261 6.7273ZM3.47361 2.89593C3.42598 2.89593 3.37881 2.90531 3.3348 2.92354C3.29079 2.94177 3.25081 2.96849 3.21712 3.00217C3.18344 3.03586 3.15672 3.07584 3.13849 3.11985C3.12026 3.16386 3.11088 3.21103 3.11088 3.25867V5.91115C3.11088 6.00735 3.1491 6.09961 3.21712 6.16764C3.28515 6.23567 3.37741 6.27388 3.47361 6.27388H6.1261C6.2223 6.27388 6.31456 6.23567 6.38259 6.16764C6.45061 6.09961 6.48883 6.00735 6.48883 5.91115V3.25867C6.48883 3.16246 6.45061 3.0702 6.38259 3.00217C6.31456 2.93415 6.2223 2.89593 6.1261 2.89593H3.47361ZM1.932 4.43755C1.932 4.37742 1.90811 4.31976 1.8656 4.27724C1.82308 4.23472 1.76542 4.21084 1.70529 4.21084H1.41057C1.31437 4.21084 1.22211 4.17262 1.15408 4.1046C1.08605 4.03657 1.04784 3.94431 1.04784 3.84811V1.19562C1.04784 1.09942 1.08605 1.00716 1.15408 0.939131C1.22211 0.871105 1.31437 0.832889 1.41057 0.832889H4.06305C4.11069 0.832889 4.15786 0.842271 4.20187 0.8605C4.24588 0.878729 4.28586 0.905448 4.31955 0.939131C4.35323 0.972814 4.37995 1.0128 4.39818 1.05681C4.41641 1.10082 4.42579 1.14799 4.42579 1.19562V1.49034C4.42579 1.55047 4.44967 1.60813 4.49219 1.65065C4.53471 1.69317 4.59237 1.71705 4.6525 1.71705C4.71262 1.71705 4.77029 1.69317 4.8128 1.65065C4.85532 1.60813 4.8792 1.55047 4.8792 1.49034V1.19562C4.8792 0.979166 4.79322 0.771575 4.64016 0.618517C4.4871 0.46546 4.27951 0.379473 4.06305 0.379473H1.41057C1.1943 0.380071 0.987055 0.46625 0.834127 0.619178C0.681199 0.772106 0.59502 0.97935 0.594421 1.19562V3.84811C0.594421 4.06456 0.680408 4.27215 0.833466 4.42521C0.986524 4.57827 1.19411 4.66426 1.41057 4.66426H1.70529C1.76542 4.66426 1.82308 4.64037 1.8656 4.59785C1.90811 4.55534 1.932 4.49767 1.932 4.43755Z" fill="#5E7CBF"/>
                                    </svg>
                                    </span>
                            </b>
                        </div>
                        <div id="ps-pro-code-copy-notification">
                            Code Copied<span class="dashicons dashicons-yes"></span>
                        </div>
                    </div>
                    <div>
                        <a href="<?php echo postman_is_bfcm() ? 'https://postmansmtp.com/cyber-monday-sale?utm_source=plugin&utm_medium=section_name&utm_campaign=BFCM&utm_id=BFCM_2024' : 'https://postmansmtp.com/pricing/?utm_source=plugin&utm_medium=wizard&utm_campaign=plugin'; ?>" target="_blank" class="button button-primary ps-yellow-btn ps-pro-product-url">CLAIM 25% OFF NOW <span class="dashicons dashicons-arrow-right-alt2"></span></a>
                    </div>
                    
                    <div>
                        <a href="" class="ps-pro-close-popup" style="color: #6A788B; font-size: 10px; font-size: 12px;">Already purchased?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
	
