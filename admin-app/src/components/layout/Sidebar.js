import { NavLink, useLocation } from 'react-router-dom';

const config = window.postSmtpAdmin || {};

const NAV_ITEMS = [
	{ to: '/', label: 'Dashboard', end: true },
	{ to: '/connections', label: 'Connections' },
	{ to: '/settings/general', label: 'Settings' },
	{ to: '/email-logs', label: 'Email Log' },
	{ to: '/notifications/admin_email', label: 'Notifications' },
	{ to: '/tools/send-test-email', label: 'Tools' },
	{ to: '/reports', label: 'Reports', pro: true },
];

const Sidebar = () => {
	const { pathname } = useLocation();

	return (
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
						className={ ( { isActive } ) => {
							const active =
								isActive ||
								( item.to?.startsWith( '/settings' ) &&
									pathname.startsWith( '/settings' ) );
							return `ps-sidebar__link${ active ? ' is-active' : '' }`;
						} }
					>
						{ item.label }
						{ item.pro && ! config.isPro && <span className="ps-pro-pill">Pro</span> }
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
			<NavLink
				to="/help-docs"
				className={ ( { isActive } ) =>
					`ps-sidebar__link${ isActive ? ' is-active' : '' }`
				}
			>
				Help &amp; Docs
			</NavLink>
		</div>
	</aside>
	);
};

export default Sidebar;
