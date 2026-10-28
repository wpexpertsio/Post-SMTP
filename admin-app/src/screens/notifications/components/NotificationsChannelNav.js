import { NavLink } from 'react-router-dom';
import { proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';
import { CHANNEL_NAV_ICONS } from '../notificationChannelAssets';

const NotificationsChannelNav = ( { channels = [], activeSlug } ) => (
	<nav className="ps-v2-notify-nav" aria-label="Notification channels" data-figma-node="672:7918">
		<ul className="ps-v2-notify-nav__list">
			{ channels.map( ( channel ) => {
				const icon = CHANNEL_NAV_ICONS[ channel.slug ];
				return (
					<li key={ channel.slug } className="ps-v2-notify-nav__item">
						<NavLink
							to={ `/notifications/${ channel.slug }` }
							className={ ( { isActive: routeActive } ) =>
								`ps-v2-notify-nav__link${ routeActive || channel.slug === activeSlug ? ' is-active' : '' }`
							}
						>
							<span className="ps-v2-notify-nav__link-inner">
								{ icon && (
									<span className="ps-v2-notify-nav__icon" aria-hidden="true">
										<img src={ icon } alt="" width="20" height="20" />
									</span>
								) }
								<span className="ps-v2-notify-nav__label">{ channel.label }</span>
								{ channel.pro_locked && (
									<span className="ps-v2-notify-pro-tag">
										<img src={ proCrownIcon } alt="" width="12" height="10" />
										PRO
									</span>
								) }
							</span>
						</NavLink>
					</li>
				);
			} ) }
		</ul>
	</nav>
);

export default NotificationsChannelNav;
