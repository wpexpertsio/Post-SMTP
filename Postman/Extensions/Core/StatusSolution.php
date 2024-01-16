<?php

class StatusSolution {

	private $status;

	public function __construct() {
		add_filter( 'post_smtp_log_solution', array( $this, 'find_solution' ), 10, 4 );
	}

	public function find_solution( $solution, $status, $log, $message ) {

		if ( empty( $status ) ) {
			return 'All good, mail sent.';
		}

		$this->status = addslashes( $status );
		$possible_solution = [];

		if ( $this->strExists('timed out') ) {
			$possible_solution[] = $this->make_clickable('https://postmansmtp.com/office365-smtp-connection-timed-out/');
		} elseif ( $this->strExists('timeout') || $this->strExists('open socket' ) ) {
			$possible_solution[] = 'Your hosting is blocking the connection, contact their support';
		} elseif ( $this->strExists( 'DATA NOT ACCEPTED' ) || $this->strExists('Exception:SendAsDeniedException' ) ) {
			$possible_solution[] = $this->make_clickable('https://postmansmtp.com/storedrv-submission-exceptionsendasdeniedexception-mapiexceptionsendasdenied/');
		} elseif ( $this->strExists( 'Incorrect authentication data') ) {
			$possible_solution[] = $this->make_clickable( 'https://postmansmtp.com/incorrect-authentication-data/' );
		} elseif ( $this->strExists( 'Unrecognized authentication type' ) ) {
			$possible_solution[] = 'Change "Authentication" type on plugin settings to "Login"';
		} elseif ( $this->strExists( 'Error executing "SendRawEmail"' ) ) {
			$possible_solution[] = 'Amazon SES - account permission error (review account configuration)';
		} elseif ( $this->strExists( 'Please log in via your web browser and then try again' ) ) {
			$possible_solution[] = $this->make_clickable( 'https://postmansmtp.com/gmail-gsuite-please-log-in-via-your-web-browser-and-then-try-again/' );
		} elseif ( $this->strExists( 'Application-specific password required' ) ) {
			$possible_solution[] = 'Two factor authentication is enabled, replace your password with app password.';
			$possible_solution[] = $this->make_clickable( 'https://support.google.com/mail/?p=InvalidSecondFactor' );
		} elseif ( $this->strExists( 'Username and Password not accepted' ) ||  $this->strExists( 'Authentication unsuccessful' ) ) {
            $possible_solution[] = 'Check you credentials, wrong email or password.';
        } elseif ( $this->strExists( 'ErrorSendAsDenied' ) ) {
            $possible_solution[] = 'Give the configured account "Send As" permissions on the "From" mailbox (admin.office365.com).';
        } elseif ( $this->strExists( 'ErrorParticipantDoesntHaveAnEmailAddress' ) ) {
		    $possible_solution[] = "Probably office 365 doesn't like shared mailbox in Reply-To field";
		} else {
			$possible_solution[] = 'Not found, check status column for more info.';
		}

		return ! empty( $possible_solution ) ? implode( '<br>', $possible_solution ) : '';
	}

	private function make_clickable($url) {
		return '<a target="_blank" href="' . esc_url($url ) . '">' . esc_html( 'Read here' ) . '</a>';
	}

	private function strExists( $value ) {
		return strpos( strtolower( $this->status ), strtolower( addslashes( $value ) ) ) !== false;
	}

}

new StatusSolution();
