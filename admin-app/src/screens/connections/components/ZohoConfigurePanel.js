import { useState } from 'react';
import { getAdminConfig } from '../../../config/admin';
import iconExternalLink from '../../../assets/dashboard/connections/helper/icon-external-link.svg';
import iconCopy from '../../../assets/dashboard/connections/helper/icon-copy.svg';

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
		<div className="ps-v2-form-field ps-v2-zoho-configure__readonly">
			<div className="ps-v2-form-field__head">
				<span className="ps-v2-form-field__label">{ label }</span>
				{ hint && <p className="ps-v2-form-field__hint">{ hint }</p> }
			</div>
			<div className="ps-v2-zoho-configure__readonly-row">
				<input
					className="ps-v2-field-input ps-v2-zoho-configure__readonly-input"
					type="text"
					readOnly
					value={ value }
					onFocus={ ( e ) => e.target.select() }
				/>
				<button
					type="button"
					className="ps-v2-zoho-configure__copy-icon-btn"
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

const buildConnectUrl = ( baseUrl, connectionId ) => {
	if ( ! baseUrl ) {
		return '';
	}
	try {
		const url = new URL( baseUrl, window.location.origin );
		if ( connectionId !== undefined && connectionId !== null ) {
			const id = parseInt( connectionId, 10 );
			if ( ! Number.isNaN( id ) && id >= 0 ) {
				url.searchParams.set( 'connection_id', String( id ) );
			}
		}
		return url.toString();
	} catch {
		return baseUrl;
	}
};

const zohoHasApiCredentials = ( values ) => {
	const clientId = String( values?.zohomail_client_id ?? '' ).trim();
	const secret = values?.zohomail_client_secret;
	if ( ! clientId || secret === undefined || secret === null ) {
		return false;
	}
	const secretStr = String( secret ).trim();
	return secretStr !== '';
};

const ZohoConnectAction = ( { zoho, connectionId, hasCredentials } ) => {
	const oauth = zoho.oauth || {};
	const connectLabel = zoho.connectLabel || 'Connect to Zoho API';
	const connectedLabel = zoho.connectedLabel || 'Connected to Zoho Mail API';

	if ( oauth.connected ) {
		return (
			<div
				className="ps-v2-zoho-configure__connect-success"
				data-figma-node="1339:17318-connected"
			>
				<span className="ps-v2-zoho-configure__connect-success-icon" aria-hidden="true">
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
				<span className="ps-v2-zoho-configure__connect-success-label">{ connectedLabel }</span>
			</div>
		);
	}

	const baseUrl = zoho.oauthConnectBaseUrl;
	const canConnect = Boolean( baseUrl ) && hasCredentials;
	const disabledHint = ! baseUrl
		? 'Zoho OAuth is unavailable. Activate Post SMTP Pro to connect.'
		: ! hasCredentials
			? 'Enter Client ID and Client Secret, then save this connection before connecting.'
			: '';

	if ( canConnect ) {
		const href = buildConnectUrl( baseUrl, connectionId );
		return (
			<a
				className="ps-v2-zoho-configure__connect-api"
				href={ href }
				data-figma-node="1381:16297"
			>
				{ connectLabel }
			</a>
		);
	}

	return (
		<button
			type="button"
			className="ps-v2-zoho-configure__connect-api ps-v2-zoho-configure__connect-api--disabled"
			disabled
			title={ disabledHint }
			data-figma-node="1381:16297"
		>
			{ connectLabel }
		</button>
	);
};

const ZohoConfigurePanel = ( { schema, values, renderField, connectionId } ) => {
	const admin = getAdminConfig();
	const zoho = admin.zohoConfigure || {};
	const credSection = schema?.sections?.api_credentials || {};
	const credentialKeys = [ 'zohomail_region', 'zohomail_client_id', 'zohomail_client_secret' ];

	const hasCredentials = zohoHasApiCredentials( values );

	return (
		<div
			className="ps-v2-zoho-configure"
			data-figma-node={ zoho.configureFigmaNode || schema?.configure_figma_node || '1339:17259' }
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
				<div className="ps-v2-configure-form__api ps-v2-zoho-configure__credentials">
					<div className="ps-v2-configure-form__api-head">
						<div>
							<h4 className="ps-v2-configure-form__section-title">
								{ credSection.title || 'API credentials' }
							</h4>
							{ credSection.description && (
								<p className="ps-v2-configure-form__section-hint">{ credSection.description }</p>
							) }
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
					{ credentialKeys.map( ( key ) => renderField( key ) ) }
					<CopyReadonlyField
						label={ zoho.redirectLabel || 'Redirect URL' }
						hint={ zoho.redirectHint }
						value={ zoho.redirectUri || '' }
					/>
					<ZohoConnectAction
						zoho={ zoho }
						connectionId={ connectionId }
						hasCredentials={ hasCredentials }
					/>
				</div>
			</div>
		</div>
	);
};

export default ZohoConfigurePanel;
