<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once 'PostmanModuleTransport.php';

if(!class_exists("PostmanSendpulseTransport")):

/**
 * Postman Sendinpulse
 * @since 2.7
 * @version 1.0
 */
class PostmanSendpulsetransport extends PostmanAbstractModuleTransport implements PostmanModuleTransport {

    const SLUG = 'sendpulse_api';
    const PORT = 2525;
    const HOST = 'smtp-pulse.com';
    const PRIORITY = 50000;
    const SENDPULSE_AUTH_OPTIONS = 'postman_sendinblue_auth_options';
    const SENDPULSE_AUTH_SECTION = 'postman_sendinblue_auth_section';

    /**
     * PostmanSendinblueTransport constructor.
     * @param $rootPluginFilenameAndPath
     * @since 2.7
     * @version 1.0
     */

    public function __construct( $rootPluginFilenameAndPath ) {

        parent::__construct ( $rootPluginFilenameAndPath );

        // add a hook on the plugins_loaded event
        add_action ( 'admin_init', array ( $this, 'on_admin_init' ) );

    }

    /**
     * @return int
     * @since 2.7
     * @version 1.0
     */
    public function getPort() {
        return self::PORT;
    }


    /**
     * @return string
     * @since 2.7
     * @version 1.0
     */
    public function getSlug() {
        return self::SLUG;
    }


     /**
     * @return string
     * @since 2.7
     * @version 1.0
     */
    public function getProtocol() {
        return 'https';
    }


     /**
     * @return string
     * @since 2.7
     * @version 1.0
     */
    public function getHostname() {
        return self::HOST;
    }


     /**
     * @since 2.7
     * @version 1.0
     */
    public function getConfigurationBid( PostmanWizardSocket $hostData, $userAuthOverride, $originalSmtpServer ) {

        $recommendation = array();
		$recommendation ['priority'] = 0;
		$recommendation ['transport'] = self::SLUG;
		$recommendation ['hostname'] = null; // scribe looks this
		$recommendation ['label'] = $this->getName();
        $recommendation['logo_url'] = $this->getLogoURL();
        
		if ($hostData->hostname == self::HOST && $hostData->port == self::PORT) {
			$recommendation ['priority'] = self::PRIORITY;
			/* translators: where variables are (1) transport name (2) host and (3) port */
			$recommendation ['message'] = sprintf ( __ ( ('Postman recommends the %1$s to host %2$s on port %3$d.') ), $this->getName(), self::HOST, self::PORT );
		}

		return $recommendation;

    }

    /**
     * @since 2.7
     * @version 1.0
     */
    public function createMailEngine() {

        $api_key = $this->options->getSendinblueApiKey();
        require_once 'PostmanSendinblueMailEngine.php';
		$engine = new PostmanSendinblueMailEngine( $api_key );

		return $engine;

    }



}
endif;

?>