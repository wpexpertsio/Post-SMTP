import { NavLink } from 'react-router-dom';
import { proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';

const NotificationsChannelNav = ( { channels = [], activeSlug } ) => (
	<nav className="ps-v2-notify-nav" aria-label="Notification channels">
		<ul className="ps-v2-notify-nav__list">
			{ channels.map( ( channel ) => {
				const isActive = channel.slug === activeSlug;
				const dotActive = channel.status_active;
				return (
					<li key={ channel.slug }>
						<NavLink
							to={ `/notifications/${ channel.slug }` }
							className={ ( { isActive: routeActive } ) =>
								`ps-v2-notify-nav__link${ routeActive || isActive ? ' is-active' : '' }`
							}
						>
							<span
								className={ `ps-v2-notify-nav__dot${ dotActive ? ' is-on' : '' }` }
								aria-hidden="true"
							/>
							<span className="ps-v2-notify-nav__label">{ channel.label }</span>
							{ channel.pro_locked && (
								<span className="ps-v2-notify-nav__pro">
									<img src={ proCrownIcon } alt="" width="12" height="10" />
									PRO
								</span>
							) }
						</NavLink>
					</li>
				);
			} ) }
		</ul>
	</nav>
);

export default NotificationsChannelNav;
