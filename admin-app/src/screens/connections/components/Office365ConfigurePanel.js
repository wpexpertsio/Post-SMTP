import { useCallback, useEffect, useRef, useState } from 'react';
import { getAdminConfig } from '../../../config/admin';
import useConnectionMutations from '../hooks/useConnectionMutations';
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

const postOneClickToggle = async ( ajax, enabled, connectionId ) => {
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
	if ( connectionId !== undefined && connectionId !== null ) {
		const id = parseInt( connectionId, 10 );
		if ( ! Number.isNaN( id ) && id >= 0 ) {
			body.append( 'connection_id', String( id ) );
		}
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

const fetchOffice365AuthUrl = async ( authUrlAjax, connectionId ) => {
	if ( ! authUrlAjax?.url || ! authUrlAjax?.action ) {
		return '';
	}
	const body = new FormData();
	body.append( 'action', authUrlAjax.action );
	if ( authUrlAjax.nonce ) {
		body.append( 'nonce', authUrlAjax.nonce );
	}
	if ( connectionId !== undefined && connectionId !== null ) {
		const id = parseInt( connectionId, 10 );
		if ( ! Number.isNaN( id ) && id >= 0 ) {
			body.append( 'connection_id', String( id ) );
		}
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

const resolveOffice365OneClickToggleOn = ( values, extensionEnabledProp, toggleCheckedProp ) => {
	if ( values?.office365_connect_tab === 'manual' ) {
		return false;
	}
	if ( values?.office365_connect_tab === 'one_click' ) {
		return true;
	}
	if ( typeof toggleCheckedProp === 'boolean' ) {
		return toggleCheckedProp;
	}
	return Boolean( extensionEnabledProp );
};

const parseConnectionIndex = ( connectionId ) => {
	if ( connectionId === undefined || connectionId === null ) {
		return null;
	}
	const id = parseInt( connectionId, 10 );
	return ! Number.isNaN( id ) && id >= 0 ? id : null;
};

/**
 * Add flow has no connection index until the row is saved; middleware needs connection_id.
 */
const ensureOffice365OneClickConnectionId = async ( {
	connectionId,
	values,
	create,
	onGrantError,
	onChange,
} ) => {
	const existing = parseConnectionIndex( connectionId );
	if ( null !== existing ) {
		return existing;
	}
	if ( ! create?.mutateAsync ) {
		onGrantError?.( 'Save the connection first, then sign in with Microsoft.' );
		return null;
	}
	const row = buildOffice365OAuthSaveRow( {
		...values,
		provider: 'office365_api',
		office365_connect_tab: 'one_click',
	} );
	const title = ( row.connection_title || row.title || '' ).trim();
	if ( ! title ) {
		onGrantError?.( 'Enter a connection title before signing in with Microsoft.' );
		return null;
	}
	const fromEmail = ( row.from_email || row.sender_email || '' ).trim();
	if ( ! fromEmail ) {
		onGrantError?.( 'Enter a From email address before signing in with Microsoft.' );
		return null;
	}
	try {
		const result = await create.mutateAsync( row );
		const newId =
			typeof result?.id === 'number' ? result.id : parseInt( result?.id, 10 );
		if ( Number.isNaN( newId ) || newId < 0 ) {
			onGrantError?.( 'Could not create a connection before Microsoft sign-in.' );
			return null;
		}
		const savedRow = result?.connections?.find( ( c ) => c.id === newId );
		if ( typeof onChange === 'function' ) {
			onChange( ( prev ) => ( {
				...prev,
				...( savedRow || row ),
				office365_connect_tab: 'one_click',
			} ) );
		}
		return newId;
	} catch ( err ) {
		onGrantError?.(
			err?.message || 'Could not save the connection before Microsoft sign-in.'
		);
		return null;
	}
};

export const buildOffice365OAuthSaveRow = ( values = {}, provider = 'office365_api' ) => {
	const fromEmail = ( values.from_email || values.sender_email || '' ).trim();
	const title = ( values.connection_title || values.title || '' ).trim();
	return {
		...values,
		provider: values.provider || provider,
		connection_title: title || values.connection_title,
		title: title || values.title,
		from_email: fromEmail,
		sender_email: fromEmail,
	};
};

const Office365OneClickBlock = ( {
	office365,
	connectionId,
	values = {},
	onChange,
	onGrantError,
} ) => {
	const admin = getAdminConfig();
	const bootstrapOneClick = office365.oneClick || admin.office365Configure?.oneClick || {};
	const { disconnectOffice365OAuth, create } = useConnectionMutations();
	const {
		title = 'Connect Microsoft 365 in one click',
		description = '',
		extensionEnabled: extensionEnabledProp,
		toggleDisabled: toggleDisabledProp,
		toggleChecked: toggleCheckedProp,
		signInUrl = '',
		connectedEmail: bootstrapConnectedEmail = '',
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

	const toggleDisabled =
		typeof toggleDisabledProp === 'boolean'
			? toggleDisabledProp
			: ! ( hasPro && isBusinessPlan );

	const canUseOneClick = hasPro && ( isBusinessPlan || ! toggleDisabled );

	const showBusinessTag =
		typeof bootstrapOneClick.showBusinessTag === 'boolean'
			? bootstrapOneClick.showBusinessTag
			: hasPro && ! isBusinessPlan;

	const initialToggleOn = resolveOffice365OneClickToggleOn(
		values,
		extensionEnabledProp,
		toggleCheckedProp
	);

	const [ extensionEnabled, setExtensionEnabled ] = useState( initialToggleOn );
	const [ toggleChecked, setToggleChecked ] = useState( initialToggleOn );
	const [ toggleBusy, setToggleBusy ] = useState( false );
	const [ liveSignInUrl, setLiveSignInUrl ] = useState( signInUrl );
	const [ authFetchBusy, setAuthFetchBusy ] = useState( false );
	const toggleRequestRef = useRef( 0 );

	const rowEmail =
		typeof values?.office365_oauth_user_email === 'string'
			? values.office365_oauth_user_email.trim()
			: '';
	const rowConnected = Boolean( values?.office365_oauth_connected );
	const hasSavedConnection =
		connectionId !== undefined &&
		connectionId !== null &&
		! Number.isNaN( parseInt( connectionId, 10 ) ) &&
		parseInt( connectionId, 10 ) >= 0;
	const connectedEmail = rowEmail || ( hasSavedConnection ? bootstrapConnectedEmail : '' );
	const isConnected =
		rowConnected || ( connectedEmail !== '' && ( rowEmail !== '' || hasSavedConnection ) );

	useEffect( () => {
		if ( signInUrl ) {
			setLiveSignInUrl( signInUrl );
		}
	}, [ signInUrl ] );

	useEffect( () => {
		if ( values?.office365_connect_tab === 'manual' ) {
			setExtensionEnabled( false );
			setToggleChecked( false );
			return;
		}
		const fromServer = resolveOffice365OneClickToggleOn(
			values,
			extensionEnabledProp,
			toggleCheckedProp
		);
		setExtensionEnabled( fromServer );
		setToggleChecked( fromServer );
	}, [
		extensionEnabledProp,
		toggleCheckedProp,
		values?.office365_connect_tab,
		values?.office365_oauth_connected,
		values?.office365_oauth_user_email,
	] );

	const sectionTitle =
		office365.oneClickSectionTitle || 'Microsoft 365 / Outlook one click';
	const sectionHint = office365.oneClickSectionHint || '';

	const signInLocked = ! canUseOneClick || ! extensionEnabled;
	const showConnected = canUseOneClick && extensionEnabled && isConnected;
	const showLockedMicrosoftBtn =
		signInLocked && ! showConnected;

	const handleOneClickDisconnect = async () => {
		if ( connectionId === undefined || connectionId === null ) {
			return;
		}
		try {
			await disconnectOffice365OAuth.mutateAsync( connectionId );
			if ( typeof onChange === 'function' ) {
				onChange( ( prev ) => ( {
					...prev,
					office365_oauth_connected: false,
					office365_oauth_user_email: '',
				} ) );
			}
		} catch {
			// Parent may surface errors.
		}
	};

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
			const result = await postOneClickToggle( toggleAjax, next, connectionId );
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
				if ( typeof onChange === 'function' ) {
					onChange( ( prev ) => ( {
						...prev,
						office365_connect_tab: serverEnabled ? 'one_click' : 'manual',
					} ) );
				}
				if ( ! serverEnabled ) {
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
		onChange,
	] );

	const handleMicrosoftSignIn = useCallback(
		async ( event ) => {
			if ( event?.preventDefault ) {
				event.preventDefault();
			}
			if ( signInLocked || isConnected || authFetchBusy ) {
				return;
			}
			setAuthFetchBusy( true );
			try {
				const grantConnectionId = await ensureOffice365OneClickConnectionId( {
					connectionId,
					values,
					create,
					onGrantError,
					onChange,
				} );
				if ( null === grantConnectionId ) {
					return;
				}
				const url = await fetchOffice365AuthUrl( authUrlAjax, grantConnectionId );
				if ( url ) {
					setLiveSignInUrl( url );
					window.location.assign( url );
				} else {
					onGrantError?.( 'Could not start Microsoft sign-in. Try again.' );
				}
			} finally {
				setAuthFetchBusy( false );
			}
		},
		[
			authFetchBusy,
			authUrlAjax,
			connectionId,
			create,
			isConnected,
			onChange,
			onGrantError,
			signInLocked,
			values,
		]
	);

	const microsoftButton = showLockedMicrosoftBtn ? (
		<span
			className="ps-v2-office365-configure__microsoft-btn ps-v2-office365-configure__microsoft-btn--brand ps-v2-office365-configure__microsoft-btn--locked"
			aria-disabled="true"
			role="presentation"
		>
			<img src={ microsoftIcon } alt="" width="19" height="19" aria-hidden="true" />
			<span>{ signInLabel }</span>
		</span>
	) : canUseOneClick && extensionEnabled && ! isConnected ? (
		<button
			type="button"
			className="ps-v2-office365-configure__microsoft-btn ps-v2-office365-configure__microsoft-btn--brand"
			onClick={ handleMicrosoftSignIn }
			disabled={ authFetchBusy }
		>
			<img src={ microsoftIcon } alt="" width="19" height="19" />
			{ authFetchBusy ? 'Redirecting…' : signInLabel }
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
						{ connectionId !== undefined && connectionId !== null ? (
							<button
								type="button"
								className="ps-v2-btn ps-v2-btn--ghost"
								disabled={ disconnectOffice365OAuth.isPending }
								onClick={ handleOneClickDisconnect }
							>
								{ disconnectOffice365OAuth.isPending ? 'Removing…' : removeLabel }
							</button>
						) : (
							removeAuthUrl && (
								<a href={ removeAuthUrl } className="ps-v2-btn ps-v2-btn--ghost">
									{ removeLabel }
								</a>
							)
						) }
					</div>
				) }
				<hr className="ps-v2-gmail-configure__panel-divider" />
			</div>
		</div>
	);
};

const buildManualAuthorizeUrl = (
	baseUrl,
	connectionId,
	mailer = 'office365_api',
	connectTab = 'manual'
) => {
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
		if ( connectTab === 'manual' || connectTab === 'one_click' ) {
			url.searchParams.set( 'office365_connect_tab', connectTab );
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
		if ( connectTab === 'manual' || connectTab === 'one_click' ) {
			href += `&office365_connect_tab=${ encodeURIComponent( connectTab ) }`;
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

const resolveOffice365ManualConnected = ( values = {}, office365 = {} ) => {
	const oneClickEnabled = Boolean( office365.oneClick?.extensionEnabled );
	if ( oneClickEnabled ) {
		return { connected: false, label: '' };
	}
	const email = typeof values?.office365_oauth_user_email === 'string' ? values.office365_oauth_user_email.trim() : '';
	const connected = Boolean( values?.office365_oauth_connected ) || email !== '';
	const label = email
		? `Connected with: ${ email }`
		: office365.manualOAuth?.connectedLabel || 'Connected to Microsoft 365 API';
	return { connected, label };
};

const Office365ManualConnectAction = ( { office365, connectionId, values, onChange } ) => {
	const { disconnectOffice365OAuth } = useConnectionMutations();
	const connectLabel =
		office365.connectApiLabel ||
		office365.oneClick?.connectApiLabel ||
		'Connect to Microsoft API';
	const removeLabel =
		office365.manualOAuth?.removeLabel ||
		office365.removeMicrosoftConnectionLabel ||
		'Remove Microsoft connection';

	const { connected, label } = resolveOffice365ManualConnected( values, office365 );

	const handleDisconnect = async () => {
		if ( connectionId === undefined || connectionId === null ) {
			return;
		}
		try {
			await disconnectOffice365OAuth.mutateAsync( connectionId );
			if ( typeof onChange === 'function' ) {
				onChange( ( prev ) => ( {
					...prev,
					office365_oauth_connected: false,
					office365_oauth_user_email: '',
				} ) );
			}
		} catch {
			// Parent can surface errors via global notice if needed.
		}
	};

	if ( connected ) {
		return (
			<div className="ps-v2-gmail-configure__manual-connected-row">
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
						{ label }
					</span>
				</div>
				<button
					type="button"
					className="ps-v2-gmail-configure__disconnect-api"
					disabled={ disconnectOffice365OAuth.isPending }
					onClick={ handleDisconnect }
				>
					{ disconnectOffice365OAuth.isPending ? 'Removing…' : removeLabel }
				</button>
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

	const connectTab = resolveOffice365ConnectTab( values, office365 );
	const authorizeUrl = buildManualAuthorizeUrl(
		office365.manualAuthorizeUrl,
		connectionId,
		'office365_api',
		connectTab
	);

	return (
		<a className="ps-v2-gmail-configure__connect-api" href={ authorizeUrl }>
			{ connectLabel }
		</a>
	);
};

const Office365ManualSection = ( { schema, office365, renderField, connectionId, values, onChange } ) => {
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
						onChange={ onChange }
					/>
				</div>
				<hr className="ps-v2-gmail-configure__panel-divider" />
			</div>
		</div>
	);
};

const Office365ConfigurePanel = ( {
	schema,
	renderField,
	connectionId,
	values = {},
	onChange,
	onGrantError,
} ) => {
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
						<Office365OneClickBlock
							office365={ office365 }
							connectionId={ connectionId }
							values={ values }
							onChange={ onChange }
							onGrantError={ onGrantError }
						/>
					) : (
						<Office365ManualSection
							schema={ schema }
							office365={ office365 }
							renderField={ renderField }
							connectionId={ connectionId }
							values={ values }
							onChange={ onChange }
						/>
					) }
				</div>
			</div>
		</div>
	);
};

export default Office365ConfigurePanel;
