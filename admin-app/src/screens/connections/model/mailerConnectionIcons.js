/**
 * Mailer icons for Connections → Choose a mailer (SVG in mailers/).
 */
import office365 from '../../../assets/dashboard/connections/mailers/microsoft.svg';
import amazonSes from '../../../assets/dashboard/connections/mailers/aws.svg';
import zoho from '../../../assets/dashboard/connections/mailers/zoho.svg';
import gmail from '../../../assets/dashboard/connections/mailers/gmail.svg';
import postmark from '../../../assets/dashboard/connections/mailers/postmark.svg';
import brevo from '../../../assets/dashboard/connections/mailers/brevo.svg';
import smtpCom from '../../../assets/dashboard/connections/mailers/smtpdotcom.svg';
import sendgrid from '../../../assets/dashboard/connections/mailers/sendgrid.svg';
import maileroo from '../../../assets/dashboard/connections/mailers/maileroo.svg';
import emailit from '../../../assets/dashboard/connections/mailers/emailit.svg';
import sweego from '../../../assets/dashboard/connections/mailers/sweego.svg';
import resend from '../../../assets/dashboard/connections/mailers/resend.svg';
import mailgun from '../../../assets/dashboard/connections/mailers/mailgun.svg';
import mailersend from '../../../assets/dashboard/connections/mailers/mailersend.svg';
import smtp from '../../../assets/dashboard/connections/mailers/other_smtp.svg';
import mailtrap from '../../../assets/dashboard/connections/mailers/mailtrap.svg';
import elasticemail from '../../../assets/dashboard/connections/mailers/elastic_email.svg';
import smtp2go from '../../../assets/dashboard/connections/mailers/smtp2go.svg';
import sparkpost from '../../../assets/dashboard/connections/mailers/sparkpost.svg';
import sendpulse from '../../../assets/dashboard/connections/mailers/sendpulse.svg';
import mandrill from '../../../assets/dashboard/connections/mailers/mandril.svg';
import cloudflare from '../../../assets/dashboard/connections/mailers/cloudflare.svg';

/** @type {Record<string, string>} */
const PROVIDER_SLUG_ALIASES = {
	default: 'smtp',
	dafault: 'smtp',
	mandrill: 'mandrill_api',
};

/**
 * @param {string|null|undefined} slug Raw provider slug from API / legacy transport.
 * @return {string}
 */
export function normalizeMailerSlug( slug ) {
	if ( ! slug ) {
		return '';
	}
	const key = String( slug ).trim();
	return PROVIDER_SLUG_ALIASES[ key ] || key;
}

/** @type {Record<string, string>} */
export const MAILER_CONNECTION_ICONS = {
	office365_api: office365,
	amazon_ses_api: amazonSes,
	zohomail_api: zoho,
	gmail_api: gmail,
	postmark_api: postmark,
	sendinblue_api: brevo,
	smtp_com_api: smtpCom,
	sendgrid_api: sendgrid,
	maileroo_api: maileroo,
	emailit_api: emailit,
	sweego_api: sweego,
	resend_api: resend,
	mailgun_api: mailgun,
	mailersend_api: mailersend,
	smtp,
	mailtrap_api: mailtrap,
	elasticemail_api: elasticemail,
	smtp2go_api: smtp2go,
	sparkpost_api: sparkpost,
	sendpulse_api: sendpulse,
	mandrill_api: mandrill,
	mandrill,
	cloudflare_email_api: cloudflare,
};

/**
 * @param {string} slug Provider slug from catalog.
 * @return {string|null}
 */
export function mailerConnectionIcon( slug ) {
	const normalized = normalizeMailerSlug( slug );
	return MAILER_CONNECTION_ICONS[ normalized ] || null;
}
