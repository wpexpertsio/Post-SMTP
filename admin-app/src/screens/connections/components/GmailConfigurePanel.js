import { useState } from '@wordpress/element';
import { getAdminConfig, getAdminUrls } from '../../../config/admin';
import iconExternalLink from '../../../assets/dashboard/connections/helper/icon-external-link.svg';
import { arrowIcon, proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';

const CopyReadonlyField = ( { label, hint, value } ) => {
	const [ copied, setCopied ] = useState( false );

	const handleCopy = async () => {
		if ( ! value ) {
			return;
		}
		try {
			await navigator.clipboard.writeText( value );
			setCopied( true );
			setTimeout( () => setCopied( false ), 2000 );
		} catch {
			// Fallback for older browsers.
			const input = document.createElement( 'textarea' );
			input.value = value;
			document.body.appendChild( input );
			input.select();
			document.execCommand( 'copy' );
			document.body.removeChild( input );
			setCopied( true );
			setTimeout( () => setCopied( false ), 2000 );
		}
	};

	return (
		<div className="ps-v2-form-field ps-v2-gmail-configure__readonly">
			<div className="ps-v2-form-field__head">
				<span className="ps-v2-form-field__label">{ label }</span>
				{ hint && <p className="ps-v2-form-field__hint">{ hint }</p> }
			</div>
			<div className="ps-v2-gmail-configure__readonly-row">
				<input
					className="ps-v2-field-input ps-v2-gmail-configure__readonly-input"
					type="text"
					readOnly
					value={ value }
					onFocus={ ( e ) => e.target.select() }
				/>
				<button
					type="button"
					className="ps-v2-btn ps-v2-btn--ghost ps-v2-gmail-configure__copy-btn"
					onClick={ handleCopy }
				>
					{ copied ? 'Copied' : 'Copy' }
				</button>
			</div>
		</div>
	);
};

const GmailOneClickBlock = () => {
	const admin = getAdminConfig();
	const urls = getAdminUrls();
	const oneClick = admin.gmailConfigure?.oneClick || {};
	const {
		title = 'Connect Gmail in one click',
		description = 'Sign in with Google and Post SMTP does the rest.',
		hasPro = false,
		extensionEnabled = false,
		signInUrl = '',
		connectedEmail = '',
		removeAuthUrl = '',
		upgradeUrl = urls.pro,
		upgradeLabel = 'Upgrade to Pro',
		signInLabel = 'Sign in with Google',
		removeLabel = 'Remove authorization',
		connectedLabel = '',
	} = oneClick;

	const showSignIn = hasPro && extensionEnabled && signInUrl && ! connectedEmail;
	const showConnected = hasPro && extensionEnabled && connectedEmail;
	const showUpgrade = ! hasPro;

	return (
		<div className="ps-v2-gmail-configure__one-click" data-figma-node="1310:15740-oneclick">
			<div className="ps-v2-gmail-configure__one-click-head">
				<h4 className="ps-v2-configure-form__section-title">{ title }</h4>
				<span className="ps-v2-gmail-configure__pro-tag">
					<img src={ proCrownIcon } alt="" width="12" height="10" />
					PRO
				</span>
			</div>
			{ description && (
				<p className="ps-v2-configure-form__section-hint">{ description }</p>
			) }
			{ showConnected && (
				<div className="ps-v2-gmail-configure__connected">
					<p className="ps-v2-gmail-configure__connected-email">
						{ connectedLabel || `Connected with: ${ connectedEmail }` }
					</p>
					{ removeAuthUrl && (
						<a href={ removeAuthUrl } className="ps-v2-btn ps-v2-btn--ghost">
							{ removeLabel }
						</a>
					) }
				</div>
			) }
			{ showSignIn && (
				<a href={ signInUrl } className="ps-v2-btn ps-v2-gmail-configure__google-btn">
					{ signInLabel }
				</a>
			) }
			{ showUpgrade && upgradeUrl && (
				<a
					href={ upgradeUrl }
					className="ps-v2-gmail-configure__upgrade-cta"
					target="_blank"
					rel="noreferrer"
				>
					{ upgradeLabel }
					<img src={ arrowIcon } alt="" width="14" height="10" />
				</a>
			) }
		</div>
	);
};

const GmailConfigurePanel = ( { schema, values, onChange, renderField } ) => {
	const admin = getAdminConfig();
	const gmail = admin.gmailConfigure || {};
	const manualKeys = [ 'oauth_client_id', 'oauth_client_secret' ];
	const manualSection = schema?.sections?.gmail_manual || {};

	return (
		<div className="ps-v2-gmail-configure">
			<div className="ps-v2-configure-form__sections">
				{ renderField( 'connection_title' ) }
				<hr className="ps-v2-configure-form__divider" />
				<div className="ps-v2-configure-form__group">
					{ renderField( 'from_name' ) }
					{ renderField( 'prevent_sender_name_override' ) }
				</div>
				<div className="ps-v2-configure-form__group">
					{ renderField( 'from_email' ) }
					{ renderField( 'prevent_sender_email_override' ) }
				</div>
				<hr className="ps-v2-configure-form__divider" />
				<div className="ps-v2-configure-form__api">
					<div className="ps-v2-configure-form__api-head">
						<div>
							<h4 className="ps-v2-configure-form__section-title">
								{ manualSection.title || gmail.manualSectionTitle || 'Connect it yourself' }
							</h4>
							<p className="ps-v2-configure-form__section-hint">
								{ manualSection.description || gmail.manualSectionHint }
							</p>
						</div>
						{ schema?.doc_url && (
							<a
								href={ schema.doc_url }
								className="ps-v2-configure-form__doc-link"
								target="_blank"
								rel="noreferrer"
							>
								<span>{ schema.doc_label || 'Get credentials' }</span>
								<img src={ iconExternalLink } alt="" width="13" height="13" />
							</a>
						) }
					</div>
					<CopyReadonlyField
						label={ gmail.redirectLabel || 'Redirect URL' }
						hint={ gmail.redirectHint }
						value={ gmail.redirectUri || '' }
					/>
					<CopyReadonlyField
						label={ gmail.jsOriginLabel || 'JavaScript origin' }
						hint={ gmail.jsOriginHint }
						value={ gmail.jsOrigin || '' }
					/>
					{ manualKeys.map( ( key ) => renderField( key ) ) }
					{ gmail.manualAuthorizeUrl && (
						<p className="ps-v2-gmail-configure__manual-auth">
							<a
								className="ps-v2-btn ps-v2-btn--primary ps-v2-gmail-configure__connect-api"
								href={ gmail.manualAuthorizeUrl }
							>
								Connect to Gmail API
							</a>
							<span className="ps-v2-gmail-configure__manual-auth-hint">
								Authorize manual OAuth after saving Client ID and Client secret.
							</span>
						</p>
					) }
				</div>
				<hr className="ps-v2-configure-form__divider" />
				<GmailOneClickBlock />
			</div>
		</div>
	);
};

export default GmailConfigurePanel;
