import { useState } from 'react';
import { getAdminConfig } from '../../../config/admin';
import iconExternalLink from '../../../assets/dashboard/connections/helper/icon-external-link.svg';
import iconCopy from '../../../assets/dashboard/connections/helper/icon-copy.svg';
import { gmailIcon, proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';

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

const GmailConnectModeTabs = ( { activeTab, onChange, gmail } ) => {
	const oneClickLabel = gmail.tabOneClickLabel || 'Connect in just one click';
	const manualLabel = gmail.tabManualLabel || 'Connect manually';

	return (
		<div
			className="ps-v2-gmail-configure__tabs"
			role="tablist"
			aria-label="Gmail connection method"
			data-figma-node={ activeTab === 'one_click' ? '1395:16363' : '1368:19603' }
		>
			<div className="ps-v2-gmail-configure__tabs-inner">
				<button
					type="button"
					role="tab"
					id="ps-gmail-tab-one-click"
					aria-selected={ activeTab === 'one_click' }
					aria-controls="ps-gmail-panel-one-click"
					className={
						activeTab === 'one_click'
							? 'ps-v2-gmail-configure__tab ps-v2-gmail-configure__tab--active'
							: 'ps-v2-gmail-configure__tab'
					}
					onClick={ () => onChange( 'one_click' ) }
				>
					{ oneClickLabel }
				</button>
				<button
					type="button"
					role="tab"
					id="ps-gmail-tab-manual"
					aria-selected={ activeTab === 'manual' }
					aria-controls="ps-gmail-panel-manual"
					className={
						activeTab === 'manual'
							? 'ps-v2-gmail-configure__tab ps-v2-gmail-configure__tab--active'
							: 'ps-v2-gmail-configure__tab'
					}
					onClick={ () => onChange( 'manual' ) }
				>
					{ manualLabel }
				</button>
			</div>
		</div>
	);
};

const GmailOneClickBlock = ( { gmail } ) => {
	const admin = getAdminConfig();
	const oneClick = admin.gmailConfigure?.oneClick || {};
	const {
		title = 'Connect Gmail in one click',
		description = 'Sign in with Gmail and Post SMTP does the rest.',
		extensionEnabled = false,
		toggleDisabled: toggleDisabledProp,
		toggleChecked: toggleCheckedProp,
		signInUrl = '',
		connectedEmail = '',
		removeAuthUrl = '',
		signInLabel = 'Sign in with Google',
		removeLabel = 'Remove authorization',
		connectedLabel = '',
	} = oneClick;

	const hasPro = Boolean( oneClick.hasPro || admin.isPro );
	const isFreeOneClick = ! hasPro;
	const showProTag =
		typeof oneClick.showProTag === 'boolean' ? oneClick.showProTag : isFreeOneClick;
	const toggleDisabled =
		typeof toggleDisabledProp === 'boolean' ? toggleDisabledProp : isFreeOneClick;

	const sectionTitle =
		gmail.oneClickSectionTitle || 'Google Workspace (Gmail) one click';
	const sectionHint =
		gmail.oneClickSectionHint ||
		'Post SMTP offers two ways to connect Gmail API with WordPress: One-Click Setup (fastest).';

	const toggleChecked =
		typeof toggleCheckedProp === 'boolean'
			? toggleCheckedProp
			: Boolean( hasPro && extensionEnabled );
	const switchDisabled = Boolean( toggleDisabled || isFreeOneClick );

	const showSignIn = hasPro && extensionEnabled && signInUrl && ! connectedEmail;
	const showConnected = hasPro && extensionEnabled && connectedEmail;
	const showLockedGoogleBtn =
		( typeof oneClick.googleSignInLocked === 'boolean'
			? oneClick.googleSignInLocked
			: isFreeOneClick ) &&
		! showSignIn &&
		! showConnected;

	const googleButton = showSignIn ? (
		<a
			href={ signInUrl }
			className="ps-v2-gmail-configure__google-btn ps-v2-gmail-configure__google-btn--brand"
		>
			<img src={ gmailIcon } alt="" width="19" height="19" />
			{ signInLabel }
		</a>
	) : showLockedGoogleBtn ? (
		<span
			className="ps-v2-gmail-configure__google-btn ps-v2-gmail-configure__google-btn--brand ps-v2-gmail-configure__google-btn--locked"
			aria-disabled="true"
			role="presentation"
			data-figma-node="1395:16418"
		>
			<img src={ gmailIcon } alt="" width="19" height="19" aria-hidden="true" />
			<span>{ signInLabel }</span>
		</span>
	) : null;

	return (
		<div
			className="ps-v2-gmail-configure__one-click-panel"
			id="ps-gmail-panel-one-click"
			role="tabpanel"
			aria-labelledby="ps-gmail-tab-one-click"
			data-figma-node={ isFreeOneClick ? '1395:16362' : '1368:19283' }
		>
			<div className="ps-v2-gmail-configure__one-click-body">
				<div className="ps-v2-gmail-configure__one-click-intro">
					<h4 className="ps-v2-configure-form__section-title">{ sectionTitle }</h4>
					{ sectionHint && (
						<p className="ps-v2-configure-form__section-hint">{ sectionHint }</p>
					) }
				</div>
				<div
					className="ps-v2-gmail-configure__one-click-action"
					data-figma-node="1395:16408"
				>
					<div className="ps-v2-gmail-configure__one-click-row">
						{ switchDisabled ? (
							<span
								className="ps-v2-gmail-configure__toggle-static"
								aria-hidden="true"
							/>
						) : (
							<label className="ps-v2-notify-switch">
								<input
									type="checkbox"
									checked={ toggleChecked }
									readOnly
									tabIndex={ 0 }
									aria-label={ title }
								/>
								<span className="ps-v2-notify-switch__track" aria-hidden="true" />
							</label>
						) }
						<div className="ps-v2-gmail-configure__one-click-copy">
							<div className="ps-v2-gmail-configure__one-click-head">
								<span className="ps-v2-gmail-configure__one-click-row-title">{ title }</span>
								{ showProTag && (
									<span className="ps-v2-notify-pro-tag">
										<img src={ proCrownIcon } alt="" width="12" height="10" />
										PRO
									</span>
								) }
							</div>
							{ description && (
								<p className="ps-v2-configure-form__section-hint">{ description }</p>
							) }
						</div>
					</div>
					{ googleButton }
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
				<hr className="ps-v2-gmail-configure__panel-divider" />
			</div>
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
			className="ps-v2-gmail-configure__connect-api"
			href={ authorizeUrl }
			data-figma-node="1368:19617"
		>
			{ connectLabel }
		</a>
	);
};

const GmailManualSection = ( { schema, gmail, renderField, connectionId } ) => {
	const manualKeys = [ 'oauth_client_id', 'oauth_client_secret' ];
	const manualSection = schema?.sections?.gmail_manual || {};

	return (
		<div
			className="ps-v2-gmail-configure__manual-panel"
			id="ps-gmail-panel-manual"
			role="tabpanel"
			aria-labelledby="ps-gmail-tab-manual"
			data-figma-node="1361:18935"
		>
			<div className="ps-v2-configure-form__api ps-v2-gmail-configure__manual-body">
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
				<div className="ps-v2-gmail-configure__manual-fields">
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
				</div>
				<div className="ps-v2-gmail-configure__manual-actions">
					<GmailManualConnectAction gmail={ gmail } connectionId={ connectionId } />
				</div>
				<hr className="ps-v2-gmail-configure__panel-divider" />
			</div>
		</div>
	);
};

const GmailConfigurePanel = ( { schema, values, onChange, renderField, connectionId } ) => {
	const admin = getAdminConfig();
	const gmail = admin.gmailConfigure || {};
	const defaultTab = gmail.defaultConnectTab === 'one_click' ? 'one_click' : 'manual';
	const [ connectTab, setConnectTab ] = useState( defaultTab );
	const freeOneClickFrame =
		connectTab === 'one_click' &&
		! ( admin.gmailConfigure?.oneClick?.hasPro || admin.isPro );

	return (
		<div
			className="ps-v2-gmail-configure"
			data-figma-node={ gmail.configureFigmaNode || '1350:18576' }
		>
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
				<div
					className="ps-v2-gmail-configure__connect-mode"
					data-figma-node={ freeOneClickFrame ? '1395:16362' : undefined }
				>
					<GmailConnectModeTabs
						activeTab={ connectTab }
						onChange={ setConnectTab }
						gmail={ gmail }
					/>
					{ connectTab === 'one_click' ? (
						<GmailOneClickBlock gmail={ gmail } />
					) : (
						<GmailManualSection
							schema={ schema }
							gmail={ gmail }
							renderField={ renderField }
							connectionId={ connectionId }
						/>
					) }
				</div>
			</div>
		</div>
	);
};

export default GmailConfigurePanel;
