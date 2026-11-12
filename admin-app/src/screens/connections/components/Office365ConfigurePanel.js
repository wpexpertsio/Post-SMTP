import { useCallback, useEffect, useRef, useState } from 'react';
import { getAdminConfig } from '../../../config/admin';
import iconExternalLink from '../../../assets/dashboard/connections/helper/icon-external-link.svg';
import iconCopy from '../../../assets/dashboard/connections/helper/icon-copy.svg';
import microsoftIcon from '../../../assets/dashboard/connections/mailers/microsoft.svg';

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

const Office365ConnectModeTabs = ( { activeTab, onChange, office365 } ) => {
	const oneClickLabel = office365.tabOneClickLabel || 'Connect in just one click';
	const manualLabel = office365.tabManualLabel || 'Connect manually';

	return (
		<div
			className="ps-v2-gmail-configure__tabs"
			role="tablist"
			aria-label="Microsoft 365 connection method"
		>
			<div className="ps-v2-gmail-configure__tabs-inner">
				<button
					type="button"
					role="tab"
					id="ps-office365-tab-one-click"
					aria-selected={ activeTab === 'one_click' }
					aria-controls="ps-office365-panel-one-click"
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
					id="ps-office365-tab-manual"
					aria-selected={ activeTab === 'manual' }
					aria-controls="ps-office365-panel-manual"
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

const postOneClickToggle = async ( ajax, enabled ) => {
	if ( ! ajax?.url || ! ajax?.action ) {
		return { ok: false };
	}
	const body = new FormData();
	body.append( 'action', ajax.action );
	body.append( 'enabled', enabled ? '1' : '0' );
	if ( ajax.nonce ) {
		body.append( '_ajax_nonce', ajax.nonce );
		body.append( 'nonce', ajax.nonce );
	}
	let json = null;
	try {
		const response = await fetch( ajax.url, {
			method: 'POST',
			body,
			credentials: 'same-origin',
		} );
		json = await response.json();
		return {
			ok: Boolean( json?.success ),
			data: json?.data,
			json,
		};
	} catch {
		return { ok: false, data: null, json };
	}
};

const fetchOffice365AuthUrl = async ( authUrlAjax ) => {
	if ( ! authUrlAjax?.url || ! authUrlAjax?.action ) {
		return '';
	}
	const body = new FormData();
	body.append( 'action', authUrlAjax.action );
	if ( authUrlAjax.nonce ) {
		body.append( 'nonce', authUrlAjax.nonce );
	}
	const response = await fetch( authUrlAjax.url, {
		method: 'POST',
		body,
		credentials: 'same-origin',
	} );
	const json = await response.json();
	if ( response.ok && json?.success && json?.data?.auth_url ) {
		return String( json.data.auth_url );
	}
	return '';
};

const Office365OneClickBlock = ( { office365 } ) => {
	const admin = getAdminConfig();
	const bootstrapOneClick = office365.oneClick || admin.office365Configure?.oneClick || {};
	const {
		title = 'Connect Microsoft 365 in one click',
		description = '',
		extensionEnabled: extensionEnabledProp,
		toggleDisabled: toggleDisabledProp,
		toggleChecked: toggleCheckedProp,
		signInUrl = '',
		connectedEmail = '',
		removeAuthUrl = '',
		signInLabel = 'Sign in with Microsoft',
		removeLabel = 'Remove authorization',
		connectedLabel = '',
		upgradeUrl = '',
		businessUpgradeLabel = 'Upgrade to Business',
		toggleAjax = {},
		authUrlAjax = {},
	} = bootstrapOneClick;

	const hasPro = Boolean( bootstrapOneClick.hasPro || admin.isPro );
	const isBusinessPlan = Boolean(
		bootstrapOneClick.isBusinessPlan || admin.isBusinessPlan
	);
	const canUseOneClick = hasPro && isBusinessPlan;

	const showBusinessTag =
		typeof bootstrapOneClick.showBusinessTag === 'boolean'
			? bootstrapOneClick.showBusinessTag
			: hasPro && ! isBusinessPlan;

	const toggleDisabled =
		typeof toggleDisabledProp === 'boolean' ? toggleDisabledProp : ! canUseOneClick;

	const [ extensionEnabled, setExtensionEnabled ] = useState(
		typeof extensionEnabledProp === 'boolean' ? extensionEnabledProp : false
	);
	const [ toggleChecked, setToggleChecked ] = useState(
		typeof toggleCheckedProp === 'boolean'
			? toggleCheckedProp
			: Boolean( canUseOneClick && extensionEnabledProp )
	);
	const [ toggleBusy, setToggleBusy ] = useState( false );
	const [ liveSignInUrl, setLiveSignInUrl ] = useState( signInUrl );
	const [ authFetchBusy, setAuthFetchBusy ] = useState( false );
	const toggleRequestRef = useRef( 0 );

	useEffect( () => {
		setLiveSignInUrl( signInUrl );
	}, [ signInUrl ] );

	useEffect( () => {
		if ( ! canUseOneClick || ! extensionEnabled || connectedEmail || liveSignInUrl ) {
			return;
		}
		let cancelled = false;
		( async () => {
			const url = await fetchOffice365AuthUrl( authUrlAjax );
			if ( ! cancelled && url ) {
				setLiveSignInUrl( url );
			}
		} )();
		return () => {
			cancelled = true;
		};
	}, [
		authUrlAjax,
		canUseOneClick,
		connectedEmail,
		extensionEnabled,
		liveSignInUrl,
	] );

	const sectionTitle =
		office365.oneClickSectionTitle || 'Microsoft 365 / Outlook one click';
	const sectionHint = office365.oneClickSectionHint || '';

	const signInLocked = ! canUseOneClick || ! extensionEnabled;
	const showSignIn =
		canUseOneClick && extensionEnabled && Boolean( liveSignInUrl ) && ! connectedEmail;
	const showConnected = canUseOneClick && extensionEnabled && connectedEmail;
	const showLockedMicrosoftBtn =
		signInLocked && ! showSignIn && ! showConnected;

	const handleToggle = useCallback( async () => {
		if ( toggleDisabled || toggleBusy ) {
			if ( bootstrapOneClick.openUpgradeOnToggle && upgradeUrl ) {
				window.open( upgradeUrl, '_blank', 'noopener,noreferrer' );
			}
			return;
		}
		const next = ! toggleChecked;
		const requestId = ++toggleRequestRef.current;
		setToggleBusy( true );
		try {
			const result = await postOneClickToggle( toggleAjax, next );
			if ( requestId !== toggleRequestRef.current ) {
				return;
			}
			if ( result.ok ) {
				const serverEnabled =
					result.data?.enabled === 1 ||
					result.data?.enabled === '1' ||
					result.data?.enabled === true;
				setToggleChecked( serverEnabled );
				setExtensionEnabled( serverEnabled );
				if ( serverEnabled && result.data?.sign_in_url ) {
					setLiveSignInUrl( String( result.data.sign_in_url ) );
				} else if ( ! serverEnabled ) {
					setLiveSignInUrl( '' );
				}
			} else {
				setToggleChecked( toggleChecked );
				setExtensionEnabled( extensionEnabled );
				if ( result.json?.data?.upgrade_url ) {
					window.open( result.json.data.upgrade_url, '_blank', 'noopener,noreferrer' );
				}
			}
		} finally {
			if ( requestId === toggleRequestRef.current ) {
				setToggleBusy( false );
			}
		}
	}, [
		bootstrapOneClick.openUpgradeOnToggle,
		toggleAjax,
		toggleBusy,
		toggleChecked,
		toggleDisabled,
		upgradeUrl,
		extensionEnabled,
	] );

	const handleMicrosoftSignIn = useCallback( async () => {
		if ( signInLocked || connectedEmail || authFetchBusy ) {
			return;
		}
		if ( liveSignInUrl ) {
			window.location.href = liveSignInUrl;
			return;
		}
		setAuthFetchBusy( true );
		try {
			const url = await fetchOffice365AuthUrl( authUrlAjax );
			if ( url ) {
				setLiveSignInUrl( url );
				window.location.href = url;
			}
		} finally {
			setAuthFetchBusy( false );
		}
	}, [
		authFetchBusy,
		authUrlAjax,
		connectedEmail,
		liveSignInUrl,
		signInLocked,
	] );

	const microsoftButton = showSignIn ? (
		<a
			href={ liveSignInUrl }
			className="ps-v2-office365-configure__microsoft-btn ps-v2-office365-configure__microsoft-btn--brand"
		>
			<img src={ microsoftIcon } alt="" width="19" height="19" />
			{ signInLabel }
		</a>
	) : showLockedMicrosoftBtn ? (
		<span
			className="ps-v2-office365-configure__microsoft-btn ps-v2-office365-configure__microsoft-btn--brand ps-v2-office365-configure__microsoft-btn--locked"
			aria-disabled="true"
			role="presentation"
		>
			<img src={ microsoftIcon } alt="" width="19" height="19" aria-hidden="true" />
			<span>{ signInLabel }</span>
		</span>
	) : canUseOneClick && extensionEnabled && ! connectedEmail ? (
		<button
			type="button"
			className="ps-v2-office365-configure__microsoft-btn ps-v2-office365-configure__microsoft-btn--brand"
			onClick={ handleMicrosoftSignIn }
			disabled={ authFetchBusy }
		>
			<img src={ microsoftIcon } alt="" width="19" height="19" />
			{ authFetchBusy ? '…' : signInLabel }
		</button>
	) : null;

	return (
		<div
			className="ps-v2-gmail-configure__one-click-panel"
			id="ps-office365-panel-one-click"
			role="tabpanel"
			aria-labelledby="ps-office365-tab-one-click"
		>
			<div className="ps-v2-gmail-configure__one-click-body">
				<div className="ps-v2-gmail-configure__one-click-intro">
					<h4 className="ps-v2-configure-form__section-title">{ sectionTitle }</h4>
					{ sectionHint && (
						<p className="ps-v2-configure-form__section-hint">{ sectionHint }</p>
					) }
				</div>
				<div className="ps-v2-gmail-configure__one-click-action">
					<div className="ps-v2-gmail-configure__one-click-row">
						{ toggleDisabled ? (
							<span
								className="ps-v2-gmail-configure__toggle-static"
								aria-hidden="true"
							/>
						) : (
							<label className="ps-v2-notify-switch">
								<input
									type="checkbox"
									checked={ toggleChecked }
									disabled={ toggleBusy }
									onChange={ handleToggle }
									aria-label={ title }
								/>
								<span className="ps-v2-notify-switch__track" aria-hidden="true" />
							</label>
						) }
						<div className="ps-v2-gmail-configure__one-click-copy">
							<div className="ps-v2-gmail-configure__one-click-head">
								<span className="ps-v2-gmail-configure__one-click-row-title">{ title }</span>
								{ showBusinessTag && (
									<span className="ps-v2-notify-business-tag">{ businessUpgradeLabel }</span>
								) }
							</div>
							{ description && (
								<p className="ps-v2-configure-form__section-hint">{ description }</p>
							) }
						</div>
					</div>
					{ microsoftButton }
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

const buildManualAuthorizeUrl = ( baseUrl, connectionId, mailer = 'office365_api' ) => {
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

const resolveOffice365ConnectTab = ( values = {}, office365 = {} ) => {
	const saved = values?.office365_connect_tab;
	if ( saved === 'one_click' || saved === 'manual' ) {
		return saved;
	}
	return office365.defaultConnectTab === 'one_click' ? 'one_click' : 'manual';
};

const office365CredentialFieldsPresent = ( values = {} ) => {
	const appId = values?.office365_app_id;
	const secret = values?.office365_app_password;
	const present = ( v ) => typeof v === 'string' && v.trim() !== '';
	return present( appId ) && present( secret );
};

const Office365ManualConnectAction = ( { office365, connectionId, values } ) => {
	const manual = office365.manualOAuth || {};
	const connectLabel =
		office365.connectApiLabel ||
		office365.oneClick?.connectApiLabel ||
		'Connect to Microsoft API';

	if ( manual.connected ) {
		return (
			<div className="ps-v2-gmail-configure__connect-success">
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
					{ manual.connectedLabel || 'Connected to Microsoft 365 API' }
				</span>
			</div>
		);
	}

	if ( ! office365.manualAuthorizeUrl ) {
		return null;
	}

	if ( ! office365CredentialFieldsPresent( values ) ) {
		return (
			<p className="ps-v2-configure-form__section-hint">
				Enter your Application (client) ID and client secret, save the connection, then connect to the Microsoft API.
			</p>
		);
	}

	if ( connectionId === undefined || connectionId === null ) {
		return (
			<p className="ps-v2-configure-form__section-hint">
				Save the connection first, then use Connect to Microsoft API on the edit screen.
			</p>
		);
	}

	const authorizeUrl = buildManualAuthorizeUrl( office365.manualAuthorizeUrl, connectionId );

	return (
		<a className="ps-v2-gmail-configure__connect-api" href={ authorizeUrl }>
			{ connectLabel }
		</a>
	);
};

const Office365ManualSection = ( { schema, office365, renderField, connectionId, values } ) => {
	const manualKeys = [ 'office365_app_id', 'office365_app_password' ];
	const manualSection = schema?.sections?.office365_manual || {};

	return (
		<div
			className="ps-v2-gmail-configure__manual-panel"
			id="ps-office365-panel-manual"
			role="tabpanel"
			aria-labelledby="ps-office365-tab-manual"
		>
			<div className="ps-v2-configure-form__api ps-v2-gmail-configure__manual-body">
				<div className="ps-v2-configure-form__api-head">
					<div>
						<h4 className="ps-v2-configure-form__section-title">
							{ manualSection.title || office365.manualSectionTitle || 'Connect it yourself' }
						</h4>
						<p className="ps-v2-configure-form__section-hint">
							{ manualSection.description || office365.manualSectionHint }
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
						label={ office365.redirectLabel || 'Redirect URL' }
						hint={ office365.redirectHint }
						value={ office365.redirectUri || '' }
					/>
					<CopyReadonlyField
						label={ office365.jsOriginLabel || 'Authorized JavaScript origin' }
						hint={ office365.jsOriginHint }
						value={ office365.jsOrigin || '' }
					/>
					{ manualKeys.map( ( key ) => renderField( key ) ) }
				</div>
				<div className="ps-v2-gmail-configure__manual-actions">
					<Office365ManualConnectAction
						office365={ office365 }
						connectionId={ connectionId }
						values={ values }
					/>
				</div>
				<hr className="ps-v2-gmail-configure__panel-divider" />
			</div>
		</div>
	);
};

const Office365ConfigurePanel = ( { schema, renderField, connectionId, values = {}, onChange } ) => {
	const admin = getAdminConfig();
	const office365 = admin.office365Configure || {};
	const connectTab = resolveOffice365ConnectTab( values, office365 );

	useEffect( () => {
		if ( typeof onChange !== 'function' ) {
			return;
		}
		const saved = values?.office365_connect_tab;
		if ( saved === 'one_click' || saved === 'manual' ) {
			return;
		}
		onChange( ( prev ) => ( {
			...prev,
			office365_connect_tab: resolveOffice365ConnectTab( prev, office365 ),
		} ) );
	}, [ values?.office365_connect_tab, onChange, office365 ] );

	const handleConnectTabChange = useCallback(
		( tab ) => {
			if ( tab !== 'one_click' && tab !== 'manual' ) {
				return;
			}
			if ( typeof onChange === 'function' ) {
				onChange( { ...values, office365_connect_tab: tab } );
			}
		},
		[ onChange, values ]
	);

	return (
		<div
			className="ps-v2-gmail-configure ps-v2-office365-configure"
			data-figma-node={ office365.configureFigmaNode || '1329:16097' }
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
				<div className="ps-v2-gmail-configure__connect-mode">
					<Office365ConnectModeTabs
						activeTab={ connectTab }
						onChange={ handleConnectTabChange }
						office365={ office365 }
					/>
					{ connectTab === 'one_click' ? (
						<Office365OneClickBlock office365={ office365 } />
					) : (
						<Office365ManualSection
							schema={ schema }
							office365={ office365 }
							renderField={ renderField }
							connectionId={ connectionId }
							values={ values }
						/>
					) }
				</div>
			</div>
		</div>
	);
};

export default Office365ConfigurePanel;
