import { useCallback, useEffect, useRef, useState } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import postSmtpLogo from '../../assets/brand/post-smtp-logo.svg';
import iconBell from '../../assets/brand/icon-bell.svg';
import iconMegaphone from '../../assets/brand/icon-megaphone.svg';
import { getAdminUrls } from '../../config/admin';
import { useHeaderNotifications } from '../../context/HeaderNotificationsContext';
import NotificationsPopupPanel from './NotificationsPopupPanel';
import AnnouncementsDrawer from './AnnouncementsDrawer';

const urls = getAdminUrls();

const NAV = [
	{ to: '/', label: 'Dashboard', end: true },
	{ to: '/connections', label: 'Connections' },
	{ to: '/settings/general', label: 'Settings' },
	{ to: '/email-logs', label: 'Email Logs' },
	{ to: '/notifications/admin_email', label: 'Notifications' },
	{ to: '/reports', label: 'Reports' },
	{ to: '/tools/send-test-email', label: 'Tools' },
];

const TopNav = () => {
	const { pathname } = useLocation();
	const anchorRef = useRef( null );
	const [ notifyOpen, setNotifyOpen ] = useState( false );
	const [ whatsNewOpen, setWhatsNewOpen ] = useState( false );
	const { unreadCount, refreshFailedLogs } = useHeaderNotifications();

	useEffect( () => {
		refreshFailedLogs();
	}, [ refreshFailedLogs ] );

	useEffect( () => {
		if ( ! notifyOpen ) {
			return undefined;
		}

		const onKeyDown = ( event ) => {
			if ( event.key === 'Escape' ) {
				setNotifyOpen( false );
			}
		};

		const onPointerDown = ( event ) => {
			if ( anchorRef.current && ! anchorRef.current.contains( event.target ) ) {
				setNotifyOpen( false );
			}
		};

		document.addEventListener( 'keydown', onKeyDown );
		document.addEventListener( 'mousedown', onPointerDown );
		return () => {
			document.removeEventListener( 'keydown', onKeyDown );
			document.removeEventListener( 'mousedown', onPointerDown );
		};
	}, [ notifyOpen ] );

	const toggleNotifyPanel = useCallback( () => {
		setNotifyOpen( ( prev ) => {
			const next = ! prev;
			if ( next ) {
				refreshFailedLogs();
			}
			return next;
		} );
	}, [ refreshFailedLogs ] );

	const closeNotifyPanel = useCallback( () => {
		setNotifyOpen( false );
	}, [] );

	const toggleWhatsNew = useCallback( () => {
		setWhatsNewOpen( ( prev ) => ! prev );
	}, [] );

	const closeWhatsNew = useCallback( () => {
		setWhatsNewOpen( false );
	}, [] );

	return (
		<header className="ps-topnav ps-topnav--v2" data-figma-node="672:5209">
			<div className="ps-topnav__inner ps-topnav__inner--v2">
				<div className="ps-topnav__start">
					<NavLink
						to="/"
						end
						className={ ( { isActive } ) =>
							`ps-topnav__brand ps-topnav__brand--v2${ isActive ? ' is-active' : '' }`
						}
						aria-label="Post SMTP"
					>
						<span className="ps-topnav__logo-mark" aria-hidden="true">
							<img className="ps-topnav__logo-img" src={ postSmtpLogo } alt="" width="38" height="40" />
						</span>
					</NavLink>

					<nav className="ps-topnav__links ps-topnav__links--v2" aria-label="Post SMTP">
						{ NAV.map( ( item ) => {
							if ( item.href ) {
								return (
									<a key={ item.label } href={ item.href } className="ps-topnav__link ps-topnav__link--v2">
										{ item.label }
									</a>
								);
							}
							return (
								<NavLink
									key={ item.label }
									to={ item.to }
									end={ item.end }
									className={ ( { isActive } ) => {
										const active =
											isActive ||
											( item.to?.startsWith( '/settings' ) &&
												pathname.startsWith( '/settings' ) );
										return `ps-topnav__link ps-topnav__link--v2${ active ? ' is-active' : '' }`;
									} }
								>
									{ item.label }
								</NavLink>
							);
						} ) }
					</nav>
				</div>

				<div className="ps-topnav__tools ps-topnav__tools--v2">
					<div className="ps-topnav__notify-anchor" ref={ anchorRef }>
						<button
							type="button"
							className={ `ps-icon-btn ps-icon-btn--v2 ps-icon-btn--alert${
								unreadCount > 0 ? ' has-unread' : ''
							}` }
							aria-label="Notifications"
							aria-expanded={ notifyOpen }
							aria-haspopup="dialog"
							onClick={ toggleNotifyPanel }
						>
							<img src={ iconBell } alt="" width="23" height="23" />
						</button>
						{ notifyOpen && <NotificationsPopupPanel onNavigate={ closeNotifyPanel } /> }
					</div>
					<button
						type="button"
						className={ `ps-icon-btn ps-icon-btn--v2${
							whatsNewOpen ? ' is-active' : ''
						}` }
						aria-label="What's New"
						aria-expanded={ whatsNewOpen }
						onClick={ toggleWhatsNew }
					>
						<img src={ iconMegaphone } alt="" width="20" height="18" />
					</button>
					<AnnouncementsDrawer open={ whatsNewOpen } onClose={ closeWhatsNew } />
				</div>
			</div>
		</header>
	);
};

export default TopNav;
