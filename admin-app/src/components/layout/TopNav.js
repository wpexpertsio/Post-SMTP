import { NavLink, useLocation } from 'react-router-dom';
import postSmtpLogo from '../../assets/brand/post-smtp-logo.svg';
import iconBell from '../../assets/brand/icon-bell.svg';
import iconMegaphone from '../../assets/brand/icon-megaphone.svg';
import { getAdminUrls } from '../../config/admin';

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
				<button type="button" className="ps-icon-btn ps-icon-btn--v2 ps-icon-btn--alert" aria-label="Notifications">
					<img src={ iconBell } alt="" width="23" height="23" />
				</button>
				<a href={ urls.docs } className="ps-icon-btn ps-icon-btn--v2" aria-label="Announcements" target="_blank" rel="noreferrer">
					<img src={ iconMegaphone } alt="" width="20" height="18" />
				</a>
			</div>
		</div>
	</header>
	);
};

export default TopNav;
