import { NavLink } from 'react-router-dom';
import { SETTINGS_NAV_ICONS } from '../settingsNavAssets';
import { SETTINGS_NAV } from '../settingsNav';

const SettingsNav = ( { activeSlug } ) => (
	<nav className="ps-v2-settings-nav" aria-label="Settings sections" data-figma-node="672:7212">
		<ul className="ps-v2-settings-nav__list">
			{ SETTINGS_NAV.map( ( item ) => {
				const icon = SETTINGS_NAV_ICONS[ item.slug ];
				return (
					<li key={ item.slug } className="ps-v2-settings-nav__item">
						<NavLink
							to={ `/settings/${ item.slug }` }
							className={ ( { isActive } ) =>
								`ps-v2-settings-nav__link${ isActive || item.slug === activeSlug ? ' is-active' : '' }`
							}
						>
							<span className="ps-v2-settings-nav__link-inner">
								{ icon && (
									<span className="ps-v2-settings-nav__icon" aria-hidden="true">
										<img src={ icon } alt="" width="20" height="20" />
									</span>
								) }
								<span className="ps-v2-settings-nav__label">{ item.label }</span>
							</span>
						</NavLink>
					</li>
				);
			} ) }
		</ul>
	</nav>
);

export default SettingsNav;
