import { useCallback, useEffect, useRef, useState } from 'react';
import { getAdminConfig } from '../../../config/admin';
import useConnectionMutations from '../hooks/useConnectionMutations';
import iconExternalLink from '../../../assets/dashboard/connections/helper/icon-external-link.svg';
import iconCopy from '../../../assets/dashboard/connections/helper/icon-copy.svg';
import { gmailIcon, proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';
import { renderConfigureSenderFields } from '../../setup-wizard/components/WizardSenderSection';
import {
	appendOAuthReturnRouteToParams,
	appendOAuthReturnRouteToUrl,
} from '../../../utils/oauthReturnRoute';

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

const fetchGmailAuthUrl = async ( authUrlAjax, connectionId, oauthReturnRoute ) => {
	if ( ! authUrlAjax?.url || ! authUrlAjax?.action ) {
		return '';
	}
	const body = new URLSearchParams();
	body.append( 'action', authUrlAjax.action );
	body.append( 'revamp', '1' );
	if ( authUrlAjax.nonce ) {
		body.append( 'nonce', authUrlAjax.nonce );
	}
	if ( connectionId !== undefined && connectionId !== null ) {
		const id = parseInt( connectionId, 10 );
		if ( ! Number.isNaN( id ) && id >= 0 ) {
			body.append( 'connection_id', String( id ) );
		}
	}
	appendOAuthReturnRouteToParams( body, oauthReturnRoute );

	const response = await fetch( authUrlAjax.url, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
		},
		body: body.toString(),
	} );

	const data = await response.json().catch( () => null );
	if ( ! data?.success || ! data?.data?.auth_url ) {
		if ( data?.data?.debug ) {
			// eslint-disable-next-line no-console
			console.error( 'Gmail one-click auth URL failed', data.data );
		}
		return '';
	}
	if ( data?.data?.debug ) {
		// eslint-disable-next-line no-console
		console.info( 'Gmail one-click auth URL debug', data.data.debug );
	}
	return data.data.auth_url;
};

const postGmailOneClickToggle = async ( ajax, enabled, connectionId ) => {
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

const gmailRowHasOAuth = ( values = {} ) => {
	const email =
		typeof values?.gmail_oauth_user_email === 'string'
			? values.gmail_oauth_user_email.trim()
			: '';
	return Boolean( values?.gmail_oauth_connected ) || email !== '';
};

const gmailOAuthClientIdLooksValid = ( clientId ) => {
	if ( typeof clientId !== 'string' ) {
		return false;
	}
	const trimmed = clientId.trim().toLowerCase();
	return trimmed.includes( 'googleusercontent.com' );
};

/**
 * Tab that actually granted OAuth on this row (not the connect-mode tab the user is viewing).
 *
 * @return {'manual'|'one_click'|null}
 */
const resolveGmailOAuthGrantTab = ( values = {} ) => {
	if ( ! gmailRowHasOAuth( values ) ) {
		return null;
	}
	const saved = values?.gmail_connect_tab;
	if ( saved === 'one_click' || saved === 'manual' ) {
		return saved;
	}
	if ( ! gmailOAuthClientIdLooksValid( values?.oauth_client_id ) ) {
		return 'one_click';
	}
	return 'manual';
};

const resolveGmailOneClickToggleOn = ( values, extensionEnabledProp, toggleCheckedProp ) => {
	const grantTab = resolveGmailOAuthGrantTab( values );
	if ( grantTab === 'manual' ) {
		return false;
	}
	if ( grantTab === 'one_click' ) {
		return true;
	}
	if ( values?.gmail_connect_tab === 'manual' ) {
		return false;
	}
	if ( values?.gmail_connect_tab === 'one_click' ) {
		return true;
	}
	if ( typeof toggleCheckedProp === 'boolean' ) {
		return toggleCheckedProp;
	}
	return Boolean( extensionEnabledProp );
};

const GmailOneClickBlock = ( {
	gmail,
	connectionId,
	values = {},
	onChange,
	onGrantError,
	oauthReturnRoute,
} ) => {
	const admin = getAdminConfig();
	const { disconnectGmailOAuth, create } = useConnectionMutations();
	const bootstrapOneClick = gmail.oneClick || admin.gmailConfigure?.oneClick || {};
	const {
		title = 'Connect Gmail in one click',
		description = 'Sign in with Gmail and Post SMTP does the rest.',
		extensionEnabled: extensionEnabledProp,
		toggleDisabled: toggleDisabledProp,
		toggleChecked: toggleCheckedProp,
		signInUrl = '',
		removeAuthUrl = '',
		signInLabel = 'Sign in with Google',
		removeLabel = 'Remove authorization',
		connectedLabel = '',
		upgradeUrl = '',
		authUrlAjax = {},
		toggleAjax = {},
	} = bootstrapOneClick;

	const hasPro = Boolean( bootstrapOneClick.hasPro || admin.isPro );
	const isFreeOneClick = ! hasPro;
	const showProTag =
		typeof bootstrapOneClick.showProTag === 'boolean'
			? bootstrapOneClick.showProTag
			: isFreeOneClick;
	const toggleDisabled =
		typeof toggleDisabledProp === 'boolean' ? toggleDisabledProp : isFreeOneClick;

	const sectionTitle =
		gmail.oneClickSectionTitle || 'Google Workspace (Gmail) one click';
	const sectionHint =
		gmail.oneClickSectionHint ||
		'Post SMTP offers two ways to connect Gmail API with WordPress: One-Click Setup (fastest).';

	const initialToggleOn = resolveGmailOneClickToggleOn(
		values,
		extensionEnabledProp,
		toggleCheckedProp
	);

	const [ extensionEnabled, setExtensionEnabled ] = useState( initialToggleOn );
	const [ toggleChecked, setToggleChecked ] = useState( initialToggleOn );
	const [ toggleBusy, setToggleBusy ] = useState( false );
	const toggleRequestRef = useRef( 0 );

	useEffect( () => {
		if ( resolveGmailOAuthGrantTab( values ) === 'manual' ) {
			setExtensionEnabled( false );
			setToggleChecked( false );
		}
	}, [ values?.gmail_connect_tab, values?.gmail_oauth_connected, values?.gmail_oauth_user_email ] );

	useEffect( () => {
		const fromServer = resolveGmailOneClickToggleOn(
			values,
			extensionEnabledProp,
			toggleCheckedProp
		);
		if ( resolveGmailOAuthGrantTab( values ) !== 'manual' ) {
			setExtensionEnabled( fromServer );
			setToggleChecked( fromServer );
		}
	}, [
		extensionEnabledProp,
		toggleCheckedProp,
		values?.gmail_connect_tab,
		values?.gmail_oauth_connected,
		values?.gmail_oauth_user_email,
	] );

	const switchDisabled = Boolean( toggleDisabled || isFreeOneClick );

	const tabOAuth = resolveGmailOAuthByTab( values, 'one_click' );
	const isConnected = tabOAuth.connected;
	const connectedEmail = tabOAuth.email;

	const [ liveSignInUrl, setLiveSignInUrl ] = useState( signInUrl );
	const [ authFetchBusy, setAuthFetchBusy ] = useState( false );

	useEffect( () => {
		setLiveSignInUrl( signInUrl );
	}, [ signInUrl ] );

	useEffect( () => {
		if (
			! hasPro ||
			! extensionEnabled ||
			isConnected ||
			liveSignInUrl ||
			null === parseConnectionIndex( connectionId )
		) {
			return;
		}
		let cancelled = false;
		( async () => {
			const url = await fetchGmailAuthUrl( authUrlAjax, connectionId, oauthReturnRoute );
			if ( ! cancelled && url ) {
				setLiveSignInUrl( url );
			}
		} )();
		return () => {
			cancelled = true;
		};
	}, [
		authUrlAjax,
		connectionId,
		extensionEnabled,
		hasPro,
		isConnected,
		liveSignInUrl,
		oauthReturnRoute,
	] );

	const signInLocked = ! hasPro || ! toggleChecked;
	const canSignIn = hasPro && toggleChecked && ! isConnected;
	const showSignInLink = canSignIn && Boolean( liveSignInUrl );
	const showConnected = hasPro && toggleChecked && isConnected;
	const showLockedGoogleBtn = signInLocked && ! canSignIn && ! showConnected;

	const handleToggle = useCallback( async () => {
		if ( switchDisabled || toggleBusy ) {
			if ( bootstrapOneClick.openUpgradeOnToggle && upgradeUrl ) {
				window.open( upgradeUrl, '_blank', 'noopener,noreferrer' );
			}
			return;
		}
		const next = ! toggleChecked;
		const requestId = ++toggleRequestRef.current;
		setToggleBusy( true );
		try {
			const result = await postGmailOneClickToggle( toggleAjax, next, connectionId );
			if ( requestId !== toggleRequestRef.current ) {
				return;
			}
			if ( result.ok ) {
				const serverEnabled =
					result.data?.enabled === 1 ||
					result.data?.enabled === '1' ||
					result.data?.enabled === true;
				const connectTab =
					result.data?.connect_tab === 'one_click' || result.data?.connect_tab === 'manual'
						? result.data.connect_tab
						: serverEnabled
							? 'one_click'
							: 'manual';
				setToggleChecked( serverEnabled );
				setExtensionEnabled( serverEnabled );
				if ( typeof onChange === 'function' ) {
					onChange( ( prev ) => ( {
						...prev,
						gmail_connect_tab: connectTab,
					} ) );
				}
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
		connectionId,
		extensionEnabled,
		onChange,
		switchDisabled,
		toggleAjax,
		toggleBusy,
		toggleChecked,
		upgradeUrl,
	] );

	const handleOneClickDisconnect = useCallback( async () => {
		if ( connectionId === undefined || connectionId === null ) {
			return;
		}
		try {
			const result = await disconnectGmailOAuth.mutateAsync( connectionId );
			const savedRow = result?.connections?.find( ( c ) => c.id === connectionId );
			if ( typeof onChange === 'function' ) {
				onChange( ( prev ) => ( {
					...prev,
					...( savedRow || {} ),
					gmail_oauth_connected: false,
					gmail_oauth_user_email: '',
					gmail_connect_tab: '',
				} ) );
			}
		} catch {
			// Parent may surface errors.
		}
	}, [ connectionId, disconnectGmailOAuth, onChange ] );

	const handleSignInClick = useCallback(
		async ( event ) => {
			if ( event?.preventDefault ) {
				event.preventDefault();
			}
			if ( signInLocked || isConnected || authFetchBusy ) {
				return;
			}
			setAuthFetchBusy( true );
			try {
				const grantConnectionId = await ensureGmailOneClickConnectionId( {
					connectionId,
					values,
					create,
					onGrantError,
					onChange,
				} );
				if ( null === grantConnectionId ) {
					return;
				}
				const url = await fetchGmailAuthUrl( authUrlAjax, grantConnectionId, oauthReturnRoute );
				if ( url ) {
					setLiveSignInUrl( url );
					window.location.assign( url );
				} else {
					onGrantError?.( 'Could not start Gmail sign-in. Try again.' );
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
			oauthReturnRoute,
			signInLocked,
			values,
		]
	);

	const googleButton = showSignInLink ? (
		<a
			href={ liveSignInUrl || '#' }
			className="ps-v2-gmail-configure__google-btn ps-v2-gmail-configure__google-btn--brand"
			onClick={ handleSignInClick }
			aria-busy={ authFetchBusy }
		>
			<img src={ gmailIcon } alt="" width="19" height="19" />
			{ authFetchBusy ? 'Redirecting…' : signInLabel }
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
	) : canSignIn ? (
		<button
			type="button"
			className="ps-v2-gmail-configure__google-btn ps-v2-gmail-configure__google-btn--brand"
			onClick={ handleSignInClick }
			disabled={ authFetchBusy }
			aria-busy={ authFetchBusy }
		>
			<img src={ gmailIcon } alt="" width="19" height="19" />
			{ authFetchBusy ? 'Redirecting…' : signInLabel }
		</button>
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
									disabled={ toggleBusy }
									onChange={ handleToggle }
									tabIndex={ 0 }
									aria-label={ title }
									aria-busy={ toggleBusy }
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
							{ connectedLabel || tabOAuth.label || `Connected with: ${ connectedEmail }` }
						</p>
						{ connectionId !== undefined && connectionId !== null ? (
							<button
								type="button"
								className="ps-v2-btn ps-v2-btn--ghost"
								disabled={ disconnectGmailOAuth.isPending }
								onClick={ handleOneClickDisconnect }
							>
								{ disconnectGmailOAuth.isPending ? 'Removing…' : removeLabel }
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
	mailer = 'gmail_api',
	connectTab = 'manual',
	oauthReturnRoute
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
			url.searchParams.set( 'gmail_connect_tab', connectTab );
		}
		return appendOAuthReturnRouteToUrl( url.toString(), oauthReturnRoute );
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
			href += `&gmail_connect_tab=${ encodeURIComponent( connectTab ) }`;
		}
		return appendOAuthReturnRouteToUrl( href, oauthReturnRoute );
	}
};

const resolveGmailConnectTab = ( values = {}, gmail = {} ) => {
	const saved = values?.gmail_connect_tab;
	if ( saved === 'one_click' || saved === 'manual' ) {
		return saved;
	}
	return gmail.defaultConnectTab === 'one_click' ? 'one_click' : 'manual';
};

/**
 * OAuth on this connection row is shown only on the tab that granted it (manual vs one-click).
 *
 * @param {'manual'|'one_click'} tab
 */
const resolveGmailOAuthByTab = ( values = {}, tab ) => {
	if ( ! gmailRowHasOAuth( values ) ) {
		return { connected: false, email: '', label: '' };
	}

	const grantTab = resolveGmailOAuthGrantTab( values );
	if ( ! grantTab || grantTab !== tab ) {
		return { connected: false, email: '', label: '' };
	}

	const email =
		typeof values?.gmail_oauth_user_email === 'string'
			? values.gmail_oauth_user_email.trim()
			: '';

	const label = email
		? `Connected with: ${ email }`
		: tab === 'one_click'
			? 'Connected with Gmail'
			: 'Connected to Gmail API';

	return { connected: true, email, label };
};

const stringFieldPresent = ( v ) => typeof v === 'string' && v.trim() !== '' && ! /^•+$/u.test( v );

/** Masked API value means a secret is already stored on this connection row. */
const storedSecretPresent = ( secret, connectionId ) => {
	if ( stringFieldPresent( secret ) ) {
		return true;
	}
	const id = typeof connectionId === 'number' ? connectionId : parseInt( connectionId, 10 );
	return (
		! Number.isNaN( id ) &&
		id >= 0 &&
		typeof secret === 'string' &&
		/^•+$/u.test( secret.trim() )
	);
};

const parseConnectionIndex = ( connectionId ) => {
	if ( connectionId === undefined || connectionId === null ) {
		return null;
	}
	const id = parseInt( connectionId, 10 );
	return ! Number.isNaN( id ) && id >= 0 ? id : null;
};

/**
 * Add flow has no postman_connections index until the row is saved; middleware needs connection_id.
 */
const ensureGmailOneClickConnectionId = async ( {
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
		onGrantError?.( 'Save the connection first, then sign in with Google.' );
		return null;
	}
	const row = buildGmailOAuthSaveRow( {
		...values,
		provider: 'gmail_api',
		gmail_connect_tab: 'one_click',
	} );
	const title = ( row.connection_title || row.title || '' ).trim();
	if ( ! title ) {
		onGrantError?.( 'Enter a connection title before signing in with Google.' );
		return null;
	}
	try {
		const result = await create.mutateAsync( row );
		const newId =
			typeof result?.id === 'number' ? result.id : parseInt( result?.id, 10 );
		if ( Number.isNaN( newId ) || newId < 0 ) {
			onGrantError?.( 'Could not create a connection before Gmail sign-in.' );
			return null;
		}
		const savedRow = result?.connections?.find( ( c ) => c.id === newId );
		if ( typeof onChange === 'function' ) {
			onChange( ( prev ) => ( {
				...prev,
				...( savedRow || row ),
				gmail_connect_tab: 'one_click',
			} ) );
		}
		return newId;
	} catch ( err ) {
		onGrantError?.( err?.message || 'Could not save the connection before Gmail sign-in.' );
		return null;
	}
};

export const buildGmailOAuthSaveRow = ( values = {}, provider = 'gmail_api' ) => {
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

const gmailOAuthPrerequisitesMet = ( values = {}, connectionId ) => {
	const clientId = values?.oauth_client_id;
	const secret = values?.oauth_client_secret;
	const fromEmail = values?.from_email || values?.sender_email;
	const title = values?.connection_title || values?.title;
	return (
		gmailOAuthClientIdLooksValid( clientId ) &&
		storedSecretPresent( secret, connectionId ) &&
		stringFieldPresent( fromEmail ) &&
		stringFieldPresent( title )
	);
};

const GmailManualConnectAction = ( {
	gmail,
	connectionId,
	values,
	onChange,
	onGrantError,
	oauthReturnRoute,
	onBeforeOAuthSave,
} ) => {
	const { disconnectGmailOAuth, update, create } = useConnectionMutations();
	const [ isConnecting, setIsConnecting ] = useState( false );
	const connectLabel = gmail.oneClick?.connectApiLabel || 'Connect to Gmail API';
	const removeLabel =
		gmail.manualOAuth?.removeLabel ||
		gmail.removeGmailConnectionLabel ||
		'Remove Gmail connection';
	const { connected, label } = resolveGmailOAuthByTab( values, 'manual' );
	const manualAuthorizeUrl =
		gmail.manualAuthorizeUrl || getAdminConfig().gmailConfigure?.manualAuthorizeUrl || '';

	const handleDisconnect = async () => {
		if ( connectionId === undefined || connectionId === null ) {
			return;
		}
		try {
			const result = await disconnectGmailOAuth.mutateAsync( connectionId );
			const savedRow = result?.connections?.find( ( c ) => c.id === connectionId );
			if ( typeof onChange === 'function' ) {
				onChange( ( prev ) => ( {
					...prev,
					...( savedRow || {} ),
					gmail_oauth_connected: false,
					gmail_oauth_user_email: '',
					gmail_connect_tab: '',
				} ) );
			}
		} catch {
			// Parent can surface errors via global notice if needed.
		}
	};

	if ( connected ) {
		return (
			<div className="ps-v2-gmail-configure__manual-connected-row">
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
						{ label }
					</span>
				</div>
				<button
					type="button"
					className="ps-v2-gmail-configure__disconnect-api"
					disabled={ disconnectGmailOAuth.isPending }
					onClick={ handleDisconnect }
				>
					{ disconnectGmailOAuth.isPending ? 'Removing…' : removeLabel }
				</button>
			</div>
		);
	}

	if ( ! manualAuthorizeUrl ) {
		return null;
	}

	const authorizeUrl = buildManualAuthorizeUrl(
		manualAuthorizeUrl,
		connectionId,
		'gmail_api',
		'manual',
		oauthReturnRoute
	);

	if ( ! gmailOAuthPrerequisitesMet( values, connectionId ) ) {
		return (
			<p className="ps-v2-configure-form__section-hint">
				Fill in connection title, From email, Gmail Client ID (ending in .googleusercontent.com), and Client secret, then connect.
			</p>
		);
	}

	const handleConnect = async ( event ) => {
		event.preventDefault();
		if ( isConnecting ) {
			return;
		}
		setIsConnecting( true );
		const row = buildGmailOAuthSaveRow( {
			...values,
			gmail_connect_tab:
				values?.gmail_connect_tab === 'one_click' || values?.gmail_connect_tab === 'manual'
					? values.gmail_connect_tab
					: 'manual',
		} );
		try {
			let grantUrl = authorizeUrl;
			let grantId;

			if ( typeof onBeforeOAuthSave === 'function' ) {
				grantId = await onBeforeOAuthSave();
			} else {
				grantId = parseConnectionIndex( connectionId );
				if ( null === grantId ) {
					const result = await create.mutateAsync( row );
					grantId =
						typeof result?.id === 'number' ? result.id : parseInt( result?.id, 10 );
				} else {
					await update.mutateAsync( {
						index: grantId,
						row,
					} );
				}
			}

			if ( Number.isNaN( grantId ) || grantId < 0 ) {
				throw new Error( 'Could not save connection before OAuth.' );
			}
			grantUrl = buildManualAuthorizeUrl(
				manualAuthorizeUrl,
				grantId,
				'gmail_api',
				'manual',
				oauthReturnRoute
			);
			window.location.assign( grantUrl );
		} catch ( err ) {
			setIsConnecting( false );
			const message =
				err?.message ||
				err?.data?.message ||
				'Could not save this connection. Check title, From email, and Gmail credentials.';
			if ( typeof onGrantError === 'function' ) {
				onGrantError( message );
			}
		}
	};

	return (
		<a
			className="ps-v2-gmail-configure__connect-api"
			href={ authorizeUrl }
			data-figma-node="1368:19617"
			aria-disabled={ isConnecting }
			onClick={ handleConnect }
		>
			{ isConnecting ? 'Saving…' : connectLabel }
		</a>
	);
};

const GmailManualSection = ( {
	schema,
	gmail,
	renderField,
	connectionId,
	values,
	onChange,
	onGrantError,
	oauthReturnRoute,
	onBeforeOAuthSave,
} ) => {
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
					<GmailManualConnectAction
						gmail={ gmail }
						connectionId={ connectionId }
						values={ values }
						onChange={ onChange }
						onGrantError={ onGrantError }
						oauthReturnRoute={ oauthReturnRoute }
						onBeforeOAuthSave={ onBeforeOAuthSave }
					/>
				</div>
				<hr className="ps-v2-gmail-configure__panel-divider" />
			</div>
		</div>
	);
};

const resolveInitialUiConnectTab = ( values, gmail ) => {
	const grantTab = resolveGmailOAuthGrantTab( values );
	if ( grantTab ) {
		return grantTab;
	}
	return resolveGmailConnectTab( values, gmail );
};

const GmailConfigurePanel = ( {
	schema,
	values = {},
	onChange,
	renderField,
	connectionId,
	onGrantError,
	layoutVariant,
	oauthReturnRoute,
	onBeforeOAuthSave,
} ) => {
	const isWizardLayout = layoutVariant === 'wizard';
	const admin = getAdminConfig();
	const gmail = admin.gmailConfigure || {};
	const [ uiConnectTab, setUiConnectTab ] = useState( () =>
		resolveInitialUiConnectTab( values, gmail )
	);
	const hadOAuthRef = useRef( gmailRowHasOAuth( values ) );

	const connectTab = uiConnectTab;
	const freeOneClickFrame =
		connectTab === 'one_click' &&
		! ( admin.gmailConfigure?.oneClick?.hasPro || admin.isPro );

	useEffect( () => {
		const hasOAuth = gmailRowHasOAuth( values );
		if ( hasOAuth && ! hadOAuthRef.current ) {
			const grantTab = resolveGmailOAuthGrantTab( values );
			if ( grantTab ) {
				setUiConnectTab( grantTab );
			}
		}
		hadOAuthRef.current = hasOAuth;
	}, [
		values?.gmail_oauth_connected,
		values?.gmail_oauth_user_email,
		values?.gmail_connect_tab,
	] );

	useEffect( () => {
		if ( typeof onChange !== 'function' ) {
			return;
		}
		if ( gmailRowHasOAuth( values ) ) {
			return;
		}
		const saved = values?.gmail_connect_tab;
		if ( saved === 'one_click' || saved === 'manual' ) {
			return;
		}
		onChange( ( prev ) => ( {
			...prev,
			gmail_connect_tab: resolveGmailConnectTab( prev, gmail ),
		} ) );
	}, [ values?.gmail_connect_tab, values?.gmail_oauth_connected, values?.gmail_oauth_user_email, onChange, gmail ] );

	const handleConnectTabChange = useCallback(
		( tab ) => {
			if ( tab !== 'one_click' && tab !== 'manual' ) {
				return;
			}
			setUiConnectTab( tab );
			if ( ! gmailRowHasOAuth( values ) && typeof onChange === 'function' ) {
				onChange( { ...values, gmail_connect_tab: tab } );
			}
		},
		[ onChange, values ]
	);

	return (
		<div
			className="ps-v2-gmail-configure"
			data-figma-node={ gmail.configureFigmaNode || '1350:18576' }
		>
			<div className="ps-v2-configure-form__sections">
				{ renderField( 'connection_title' ) }
				<hr className="ps-v2-configure-form__divider" />
				{ renderConfigureSenderFields( {
					isWizardLayout,
					renderField,
					values,
					onChange,
				} ) }
				<hr className="ps-v2-configure-form__divider" />
				<div
					className="ps-v2-gmail-configure__connect-mode"
					data-figma-node={ freeOneClickFrame ? '1395:16362' : undefined }
				>
					<GmailConnectModeTabs
						activeTab={ connectTab }
						onChange={ handleConnectTabChange }
						gmail={ gmail }
					/>
					{ connectTab === 'one_click' ? (
						<GmailOneClickBlock
							gmail={ gmail }
							connectionId={ connectionId }
							values={ values }
							onChange={ onChange }
							onGrantError={ onGrantError }
							oauthReturnRoute={ oauthReturnRoute }
						/>
					) : (
						<GmailManualSection
							schema={ schema }
							gmail={ gmail }
							renderField={ renderField }
							connectionId={ connectionId }
							values={ values }
							onChange={ onChange }
							onGrantError={ onGrantError }
							oauthReturnRoute={ oauthReturnRoute }
							onBeforeOAuthSave={ onBeforeOAuthSave }
						/>
					) }
				</div>
			</div>
		</div>
	);
};

export default GmailConfigurePanel;
