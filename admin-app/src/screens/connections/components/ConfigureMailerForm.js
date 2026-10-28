import { getAdminUrls } from '../../../config/admin';
import FormGenerator from '../../../components/form-generator/FormGenerator';
import GmailConfigurePanel from './GmailConfigurePanel';
import { mailerConnectionIcon } from '../model/mailerConnectionIcons';
import useMailerCatalog from '../hooks/useMailerCatalog';
import iconExternalLink from '../../../assets/dashboard/connections/helper/icon-external-link.svg';

const OAUTH_PROVIDERS = {
	gmail_api: 'gmail_api',
	office365_api: 'office365_api',
	zohomail_api: 'zohomail_api',
};

/** Figma configure panels — shared field layout + credentials section per mailer. */
const FIGMA_CONFIGURE_MAILERS = {
	sendgrid_api: {
		figmaNode: '1310:17091',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'sendgrid_api_key' ],
	},
	mailgun_api: {
		figmaNode: '1310:17481',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'mailgun_api_key', 'mailgun_domain_name' ],
	},
	cloudflare_email_api: {
		figmaNode: '1310:16703',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'cloudflare_email_api_key' ],
	},
	smtp: {
		figmaNode: '1310:17869',
		credentialsSection: 'smtp_server_details',
		credentialKeys: [ 'hostname', 'port', 'basic_auth_username', 'basic_auth_password' ],
	},
	postmark_api: {
		figmaNode: '1310:18738',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'postmark_api_key' ],
	},
	sendinblue_api: {
		figmaNode: '1310:19144',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'sendinblue_api_key' ],
	},
	sendpulse_api: {
		figmaNode: '1310:19536',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'sendpulse_api_key', 'sendpulse_secret_key' ],
	},
	mailersend_api: {
		figmaNode: '1310:20468',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'mailersend_api_key' ],
	},
	smtp2go_api: {
		figmaNode: '1310:20854',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'smtp2go_api_key' ],
	},
	elasticemail_api: {
		figmaNode: '1310:21248',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'elasticemail_api_key' ],
	},
	sparkpost_api: {
		figmaNode: '1310:21637',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'sparkpost_api_key' ],
	},
	smtp_com_api: {
		figmaNode: '1310:22026',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'smtp_com_api_key' ],
	},
	maileroo_api: {
		figmaNode: '1310:22498',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'maileroo_api_key' ],
	},
	emailit_api: {
		figmaNode: '1310:22887',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'emailit_api_key' ],
	},
	mailtrap_api: {
		figmaNode: '1310:23278',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'mailtrap_api_key' ],
	},
	sweego_api: {
		figmaNode: '1310:23669',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'sweego_api_key' ],
	},
	resend_api: {
		figmaNode: '1339:17669',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'resend_api_key' ],
	},
	mandrill_api: {
		figmaNode: '1339:18062',
		credentialsSection: 'api_credentials',
		credentialKeys: [ 'mandrill_api_key' ],
	},
};

const buildFigmaConfigureBlocks = ( config ) => [
	{ type: 'fields', keys: [ 'connection_title' ] },
	{ type: 'divider' },
	{ type: 'group', keys: [ 'from_name', 'prevent_sender_name_override' ] },
	{ type: 'group', keys: [ 'from_email', 'prevent_sender_email_override' ] },
	{ type: 'divider' },
	{
		type: 'credentials_section',
		sectionKey: config.credentialsSection,
		keys: config.credentialKeys,
	},
];

const ConfigureMailerForm = ( { schema, values, onChange, provider, connectionId } ) => {
	const urls = getAdminUrls();
	const catalogQuery = useMailerCatalog();
	const catalogItem = catalogQuery.data?.items?.find( ( item ) => item.slug === provider );
	const isGmailOAuthLayout = schema?.configure_layout === 'gmail_oauth' || provider === 'gmail_api';
	const oauthSlug = OAUTH_PROVIDERS[ provider ];
	const oauthUrl =
		! isGmailOAuthLayout && oauthSlug
			? `${ urls.wizard }&socket=${ oauthSlug }&step=2`
			: null;

	const displayName = schema?.display_name || catalogItem?.label || 'Mailer';
	const configureTitle = schema?.configure_title || `Configure ${ displayName }`;
	const configureSubtitle = schema?.description || '';
	const iconUrl = mailerConnectionIcon( provider );
	const figmaMailer = FIGMA_CONFIGURE_MAILERS[ provider ];
	const useFigmaLayout = Boolean( figmaMailer );
	const figmaBlocks = figmaMailer ? buildFigmaConfigureBlocks( figmaMailer ) : [];

	const renderField = ( key ) => {
		const field = schema?.fields?.[ key ];
		if ( ! field ) {
			return null;
		}
		return (
			<FormGenerator
				key={ key }
				schema={ { fields: { [ key ]: field }, field_sequence: [ key ] } }
				values={ values }
				onChange={ onChange }
			/>
		);
	};

	const renderCredentialsSection = ( sectionKey, keys ) => {
		const section = schema?.sections?.[ sectionKey ] || schema?.sections?.api_credentials;
		const defaultTitle = sectionKey === 'smtp_server_details' ? 'SMTP server details' : 'API credentials';
		return (
			<div className="ps-v2-configure-form__api">
				<div className="ps-v2-configure-form__api-head">
					<div>
						<h4 className="ps-v2-configure-form__section-title">{ section?.title || defaultTitle }</h4>
						{ section?.description && (
							<p className="ps-v2-configure-form__section-hint">{ section.description }</p>
						) }
					</div>
					{ schema?.doc_url && (
						<a href={ schema.doc_url } className="ps-v2-configure-form__doc-link" target="_blank" rel="noreferrer">
							<span>{ schema.doc_label || 'Get credentials' }</span>
							<img src={ iconExternalLink } alt="" width="13" height="13" />
						</a>
					) }
				</div>
				{ keys.map( ( key ) => renderField( key ) ) }
			</div>
		);
	};

	return (
		<div
			className="ps-v2-configure-form"
			data-figma-node={
				isGmailOAuthLayout
					? schema?.configure_figma_node || '1310:15740'
					: figmaMailer?.figmaNode || '672:5588'
			}
		>
			<header className="ps-v2-configure-form__hero">
				<div className="ps-v2-configure-form__hero-icon" aria-hidden="true">
					{ iconUrl ? <img src={ iconUrl } alt="" width="30" height="28" /> : null }
				</div>
				<div>
					<h3 className="ps-v2-configure-form__hero-title">{ configureTitle }</h3>
					{ configureSubtitle && (
						<p className="ps-v2-configure-form__hero-sub">{ configureSubtitle }</p>
					) }
				</div>
			</header>

			{ oauthUrl && (
				<p className="ps-v2-configure-form__oauth">
					<a className="ps-v2-btn ps-v2-btn--primary" href={ oauthUrl }>
						Authorize with provider
					</a>
					<span className="ps-v2-configure-form__oauth-hint">
						Complete OAuth in the wizard, then return here to save.
					</span>
				</p>
			) }

			{ isGmailOAuthLayout ? (
				<GmailConfigurePanel
					schema={ schema }
					values={ values }
					onChange={ onChange }
					renderField={ renderField }
					connectionId={ connectionId }
				/>
			) : useFigmaLayout ? (
				<div className="ps-v2-configure-form__sections">
					{ figmaBlocks.map( ( block, index ) => {
						if ( block.type === 'divider' ) {
							return <hr key={ `div-${ index }` } className="ps-v2-configure-form__divider" />;
						}
						if ( block.type === 'credentials_section' ) {
							return (
								<div key={ `cred-${ index }` }>
									{ renderCredentialsSection( block.sectionKey, block.keys ) }
								</div>
							);
						}
						if ( block.type === 'group' ) {
							return (
								<div key={ `grp-${ index }` } className="ps-v2-configure-form__group">
									{ block.keys.map( ( key ) => renderField( key ) ) }
								</div>
							);
						}
						return (
							<div key={ `fld-${ index }` }>
								{ block.keys.map( ( key ) => renderField( key ) ) }
							</div>
						);
					} ) }
				</div>
			) : (
				<FormGenerator schema={ schema } values={ values } onChange={ onChange } />
			) }
		</div>
	);
};

export default ConfigureMailerForm;
