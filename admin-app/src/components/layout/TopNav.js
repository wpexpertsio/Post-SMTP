import { NavLink } from 'react-router-dom';
import {
	IconBell,
	IconBook,
	IconConnections,
	IconDashboard,
	IconEmailLog,
	IconHelp,
	IconPlane,
	IconSearch,
	IconSettings,
	IconTool,
} from '../ui/Icons';

const config = window.postSmtpAdmin || {};
const urls = config.adminUrls || {};

const NAV = [
	{ to: '/', label: 'Dashboard', end: true, icon: IconDashboard },
	{ to: '/connections', label: 'Connections', icon: IconConnections },
	{ href: urls.settings, label: 'Settings', icon: IconSettings },
	{ href: urls.emailLog, label: 'Email Log', icon: IconEmailLog },
	{ href: urls.settings + '#notifications', label: 'Notifications', icon: IconBell },
	{ href: urls.diagnostics, label: 'Troubleshooting', icon: IconTool },
];

const TopNav = () => (
	<header className="ps-topnav">
		<div className="ps-topnav__inner">
			<div className="ps-topnav__brand">
				<span className="ps-topnav__logo"><IconPlane /></span>
				<div>
					<strong>Post SMTP</strong>
					<span>v{ config.version || '4.0' } · { config.isPro ? 'Pro' : 'Free' }</span>
				</div>
			</div>

			<nav className="ps-topnav__links">
				{ NAV.map( ( item ) => {
					const Icon = item.icon;
					if ( item.href ) {
						return (
							<a key={ item.label } href={ item.href } className="ps-topnav__link">
								<Icon />
								{ item.label }
							</a>
						);
					}
					return (
						<NavLink
							key={ item.label }
							to={ item.to }
							end={ item.end }
							className={ ( { isActive } ) => `ps-topnav__link${ isActive ? ' is-active' : '' }` }
						>
							<Icon />
							{ item.label }
						</NavLink>
					);
				} ) }
			</nav>

			<div className="ps-topnav__tools">
				<button type="button" className="ps-icon-btn" aria-label="Search"><IconSearch /></button>
				<a href={ urls.docs } className="ps-icon-btn" aria-label="Documentation" target="_blank" rel="noreferrer"><IconBook /></a>
				<a href={ urls.docs } className="ps-icon-btn" aria-label="Help" target="_blank" rel="noreferrer"><IconHelp /></a>
				<button type="button" className="ps-icon-btn ps-icon-btn--alert" aria-label="Notifications"><IconBell /></button>
			</div>
		</div>
	</header>
);

export default TopNav;
