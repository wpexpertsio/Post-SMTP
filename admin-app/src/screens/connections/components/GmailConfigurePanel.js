import { useState } from 'react';
import { getAdminConfig, getAdminUrls } from '../../../config/admin';
import iconExternalLink from '../../../assets/dashboard/connections/helper/icon-external-link.svg';
import iconCopy from '../../../assets/dashboard/connections/helper/icon-copy.svg';
import { proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';

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
					className="ps-v2-gmail-configure__copy-icon-btn"
					onClick={ handleCopy }
					aria-label={ copied ? 'Copied' : 'Copy' }
					title={ copied ? 'Copied' : 'Copy' }
				>
					<img src={ iconCopy } alt="" width="38" height="38" />
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
		description = 'Sign in with Gmail and Post SMTP does the rest.',
		hasPro = false,
		extensionEnabled = false,
		toggleDisabled = ! hasPro,
		toggleChecked: toggleCheckedProp,
		openUpgradeOnToggle = ! hasPro,
		signInUrl = '',
		connectedEmail = '',
		removeAuthUrl = '',
		upgradeUrl = urls.pro,
		upgradeLabel = 'Upgrade to Pro',
		signInLabel = 'Sign in with Google',
		removeLabel = 'Remove authorization',
		connectedLabel = '',
	} = oneClick;

	const toggleChecked =
		typeof toggleCheckedProp === 'boolean'
			? toggleCheckedProp
			: Boolean( hasPro && extensionEnabled );
	const switchDisabled = Boolean( toggleDisabled || ( ! hasPro && openUpgradeOnToggle ) );

	const showSignIn = hasPro && extensionEnabled && signInUrl && ! connectedEmail;
	const showConnected = hasPro && extensionEnabled && connectedEmail;

	const handleToggleClick = ( event ) => {
		if ( ! switchDisabled ) {
			return;
		}
		event.preventDefault();
		if ( openUpgradeOnToggle && upgradeUrl ) {
			window.open( upgradeUrl, '_blank', 'noopener,noreferrer' );
		}
	};

	return (
		<div className="ps-v2-gmail-configure__one-click" data-figma-node="1329:15669">
			<div className="ps-v2-gmail-configure__one-click-row">
				<label
					className={
						switchDisabled
							? 'ps-v2-notify-switch ps-v2-notify-switch--disabled'
							: 'ps-v2-notify-switch'
					}
					onClick={ handleToggleClick }
				>
					<input
						type="checkbox"
						checked={ toggleChecked }
						disabled={ switchDisabled }
						readOnly
						tabIndex={ switchDisabled ? -1 : 0 }
						aria-label={ title }
					/>
					<span className="ps-v2-notify-switch__track" aria-hidden="true" />
				</label>
				<div className="ps-v2-gmail-configure__one-click-copy">
					<div className="ps-v2-gmail-configure__one-click-head">
						<h4 className="ps-v2-configure-form__section-title">{ title }</h4>
						<span className="ps-v2-notify-pro-tag">
							<img src={ proCrownIcon } alt="" width="12" height="10" />
							PRO
						</span>
					</div>
					{ description && (
						<p className="ps-v2-configure-form__section-hint">{ description }</p>
					) }
				</div>
			</div>
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
		</div>
	);
};

const buildManualAuthorizeUrl = ( baseUrl, connectionId, mailer = 'gmail_api' ) => {
	if ( ! baseUrl ) {
		return baseUrl;
	}
	try {
		const url = new URL( baseUrl, window.location.origin );
		if ( connectionId !== undefined && connectionId !== null ) {
			const id = parseInt( connectionId, 10 );
			if ( ! Number.isNaN( id ) && id >= 0 ) {
				url.searchParams.set( 'connection_id', String( id ) );
			}
		}
		if ( mailer ) {
			url.searchParams.set( 'mailer', mailer );
		}
		return url.toString();
	} catch {
		const separator = baseUrl.includes( '?' ) ? '&' : '?';
		let href = `${ baseUrl }${ separator }mailer=${ encodeURIComponent( mailer ) }`;
		if ( connectionId !== undefined && connectionId !== null ) {
			const id = parseInt( connectionId, 10 );
			if ( ! Number.isNaN( id ) && id >= 0 ) {
				href += `&connection_id=${ id }`;
			}
		}
		return href;
	}
};

const GmailManualConnectAction = ( { gmail, connectionId } ) => {
	const manual = gmail.manualOAuth || {};
	const connectLabel = gmail.oneClick?.connectApiLabel || 'Connect to Gmail API';
	const oneClick = gmail.oneClick || {};
	const hideManualConnect =
		oneClick.hasPro && oneClick.extensionEnabled && ! manual.connected;

	if ( manual.connected ) {
		return (
			<div
				className="ps-v2-gmail-configure__connect-success"
				data-figma-node="1329:15734-connected"
			>
				<span className="ps-v2-gmail-configure__connect-success-icon" aria-hidden="true">
					<svg width="12" height="10" viewBox="0 0 12 10" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path
							d="M1 5.2L4.2 8.4L11 1.4"
							stroke="currentColor"
							strokeWidth="2"
							strokeLinecap="round"
							strokeLinejoin="round"
						/>
					</svg>
				</span>
				<span className="ps-v2-gmail-configure__connect-success-label">
					{ manual.connectedLabel || 'Connected to Gmail API' }
				</span>
			</div>
		);
	}

	if ( hideManualConnect || ! gmail.manualAuthorizeUrl ) {
		return null;
	}

	const authorizeUrl = buildManualAuthorizeUrl( gmail.manualAuthorizeUrl, connectionId );

	return (
		<a
			className="ps-v2-btn ps-v2-gmail-configure__connect-api"
			href={ authorizeUrl }
			data-figma-node="1329:15734"
		>
			{ connectLabel }
		</a>
	);
};

const GmailManualSection = ( { schema, gmail, renderField, connectionId } ) => {
	const manualKeys = [ 'oauth_client_id', 'oauth_client_secret' ];
	const manualSection = schema?.sections?.gmail_manual || {};

	return (
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
			<GmailManualConnectAction gmail={ gmail } connectionId={ connectionId } />
		</div>
	);
};

const GmailConfigurePanel = ( { schema, values, onChange, renderField, connectionId } ) => {
	const admin = getAdminConfig();
	const gmail = admin.gmailConfigure || {};

	return (
		<div className="ps-v2-gmail-configure" data-figma-node={ gmail.configureFigmaNode || '1329:15620' }>
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
				<GmailOneClickBlock />
				<hr className="ps-v2-configure-form__divider" />
				<GmailManualSection schema={ schema } gmail={ gmail } renderField={ renderField } connectionId={ connectionId } />
			</div>
		</div>
	);
};

export default GmailConfigurePanel;
