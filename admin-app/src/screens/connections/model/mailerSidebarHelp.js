/**
 * Sidebar “Setup helper” / “Need a hand?” copy per mailer (Figma 672:5689, 672:5704).
 */

const SETUP_TAG = 'Setup helper';

const EXPERT_CARD = {
	tag: 'Need a hand?',
	title: 'Expert configuration session',
	body: 'Get 1:1 help setting up your mailer.',
	cta: 'Book now',
	url: 'https://postmansmtp.com/pricing/',
};

const DOCS_MAILER_GUIDE =
	'https://postmansmtp.com/documentation/sockets-addons/post-smtp-complete-mailer-guide/';

const SETUP_LINK_CTA = 'View documentation';

/**
 * @param {string} title
 * @param {string} body
 * @param {string} url
 */
function setupCard( title, body, url ) {
	return {
		tag: SETUP_TAG,
		title,
		body,
		cta: SETUP_LINK_CTA,
		url,
	};
}

/** @type {Record<string, ReturnType<typeof setupCard>>} */
const MAILER_SETUP = {
	gmail_api: setupCard(
		'What is Gmail?',
		'Send from Google Workspace or Gmail with secure OAuth.',
		'https://postmansmtp.com/documentation/sockets-addons/gmail/'
	),
	office365_api: setupCard(
		'What is Microsoft 365?',
		'Connect Outlook / Microsoft 365 for business email.',
		'https://postmansmtp.com/documentation/sockets-addons/how-to-configure-post-smtp-with-office-365/'
	),
	amazon_ses_api: setupCard(
		'What is Amazon SES?',
		'High-volume sending with AWS Simple Email Service.',
		'https://postmansmtp.com/documentation/sockets-addons/amazon-ses-pro/'
	),
	sendgrid_api: setupCard(
		'What is SendGrid?',
		'Reliable sending for busy sites.',
		'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-sendgrid-with-post-smtp/'
	),
	mailgun_api: setupCard(
		'What is Mailgun?',
		'Developer-friendly API delivery and logs.',
		'https://postmansmtp.com/documentation/sockets-addons/how-to-configure-post-smtp-with-mail-gun/'
	),
	cloudflare_email_api: setupCard(
		'What is Cloudflare Email?',
		'Send from addresses on domains you manage in Cloudflare.',
		DOCS_MAILER_GUIDE
	),
	smtp: setupCard(
		'What is Other SMTP?',
		'Use any SMTP host, port, and credentials you already have.',
		'https://postmansmtp.com/documentation/sockets-addons/configure-post-smtp-with-other-smtp/'
	),
	zohomail_api: setupCard(
		'What is Zoho Mail?',
		'Send through your Zoho Mail account with OAuth.',
		'https://postmansmtp.com/documentation/sockets-addons/zoho-with-post-smtp/'
	),
	postmark_api: setupCard(
		'What is Postmark?',
		'Fast transactional email with great deliverability.',
		'https://postmansmtp.com/documentation/sockets-addons/postmark/'
	),
	sendinblue_api: setupCard(
		'What is Brevo?',
		'Marketing and transactional email from Brevo (Sendinblue).',
		'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-sendinblue-aka-brevo-with-post-smtp/'
	),
	sendpulse_api: setupCard(
		'What is SendPulse?',
		'Automated campaigns and transactional sending.',
		'https://postmansmtp.com/documentation/sockets-addons/configure-post-smtp-with-sendpulse/'
	),
	mailersend_api: setupCard(
		'What is MailerSend?',
		'Simple API sending for apps and WordPress.',
		'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-mailersend-with-post-smtp/'
	),
	smtp2go_api: setupCard(
		'What is SMTP2GO?',
		'Global SMTP relay with detailed delivery reports.',
		'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-smtp2go-with-post-smtp/'
	),
	elasticemail_api: setupCard(
		'What is Elastic Email?',
		'Affordable API and SMTP for high volume.',
		'https://postmansmtp.com/documentation/sockets-addons/configure-post-smtp-with-elastic-email/'
	),
	sparkpost_api: setupCard(
		'What is SparkPost?',
		'Enterprise-grade email analytics and delivery.',
		'https://postmansmtp.com/documentation/sockets-addons/sparkpost/'
	),
	smtp_com_api: setupCard(
		'What is SMTP.com?',
		'API-based sending without manual SMTP settings.',
		'https://postmansmtp.com/docs/mailers/how-to-setup-smtp-com-with-post-smtp/'
	),
	maileroo_api: setupCard(
		'What is Maileroo?',
		'Transactional email API built for deliverability.',
		'https://postmansmtp.com/docs/mailers/how-to-setup-maileroo-with-post-smtp/'
	),
	emailit_api: setupCard(
		'What is Emailit?',
		'Send authenticated mail after domain verification.',
		'https://postmansmtp.com/docs/mailers/emailit-with-post-smtp/'
	),
	mailtrap_api: setupCard(
		'What is Mailtrap?',
		'Production sending with tracking and analytics.',
		'https://postmansmtp.com/docs/mailers/how-to-setup-mailtrap-with-post-smtp/'
	),
	sweego_api: setupCard(
		'What is Sweego?',
		'European email API with strong deliverability.',
		'https://postmansmtp.com/docs/mailers/how-to-setup-sweego-with-post-smtp/'
	),
	resend_api: setupCard(
		'What is Resend?',
		'Modern API email for developers and WordPress.',
		'https://postmansmtp.com/docs/mailers/how-to-setup-resend-with-post-smtp/'
	),
	mandrill_api: setupCard(
		'What is Mandrill?',
		'Transactional email from Mailchimp’s Mandrill.',
		'https://postmansmtp.com/documentation/sockets-addons/how-to-setup-mandrill-with-post-smtp/'
	),
};

/**
 * @param {string|null} providerSlug
 * @return {{ setup: object, expert: object }|null}
 */
export function mailerSidebarHelp( providerSlug ) {
	if ( ! providerSlug ) {
		return null;
	}

	const setup =
		MAILER_SETUP[ providerSlug ] ||
		setupCard(
			'Set up your mailer',
			'Follow our guide to connect this provider in a few steps.',
			DOCS_MAILER_GUIDE
		);

	return {
		setup,
		expert: { ...EXPERT_CARD },
	};
}
