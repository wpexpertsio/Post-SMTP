import { NavLink } from 'react-router-dom';

const config = window.postSmtpAdmin || {};
const urls = config.adminUrls || {};

const NAV_ITEMS = [
	{ to: '/', label: 'Dashboard', end: true },
	{ to: '/connections', label: 'Connections' },
	{ href: urls.settings, label: 'Settings', external: true },
	{ href: urls.emailLog, label: 'Email Log', external: true },
	{ href: urls.settings + '#notifications', label: 'Notifications', external: true },
	{ href: urls.diagnostics, label: 'Troubleshooting', external: true },
	{ href: urls.pro, label: 'Reports', external: true, pro: true },
	{ href: urls.settings + '#mobile-app', label: 'Mobile App', external: true, pro: true },
];

const Sidebar = () => (
	<aside className="ps-sidebar">
		<div className="ps-sidebar__brand">
			<div className="ps-sidebar__logo" aria-hidden="true">✉</div>
			<div>
				<strong>Post SMTP</strong>
				<span className="ps-sidebar__badge">
					v{ config.version || '4.0' } · { config.isPro ? 'Pro' : 'Free' }
				</span>
			</div>
		</div>

		<nav className="ps-sidebar__nav">
			{ NAV_ITEMS.map( ( item ) => {
				if ( item.external ) {
					return (
						<a
							key={ item.label }
							href={ item.href || '#' }
							className="ps-sidebar__link"
							target={ item.href?.startsWith( 'http' ) ? '_blank' : undefined }
							rel={ item.href?.startsWith( 'http' ) ? 'noreferrer' : undefined }
						>
							{ item.label }
							{ item.pro && ! config.isPro && <span className="ps-pro-pill">Pro</span> }
						</a>
					);
				}

				return (
					<NavLink
						key={ item.label }
						to={ item.to }
						end={ item.end }
						className={ ( { isActive } ) =>
							`ps-sidebar__link${ isActive ? ' is-active' : '' }`
						}
					>
						{ item.label }
					</NavLink>
				);
			} ) }

			{ config.isLegacy && (
				<NavLink
					to="/migration"
					className={ ( { isActive } ) =>
						`ps-sidebar__link ps-sidebar__link--migration${ isActive ? ' is-active' : '' }`
					}
				>
					Migration
				</NavLink>
			) }
		</nav>

		<div className="ps-sidebar__footer">
			<a href={ urls.docs || '#' } className="ps-sidebar__link" target="_blank" rel="noreferrer">
				Help &amp; Docs
			</a>
		</div>
	</aside>
);

export default Sidebar;
