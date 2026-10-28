import { NavLink } from 'react-router-dom';
import { TOOLS_NAV } from '../toolsNav';
import { TOOLS_NAV_ICONS } from '../toolsNavAssets';

const ToolsSubnav = ( { activeSlug } ) => (
	<nav className="ps-v2-tools-nav" aria-label="Tools" data-figma-node="547:6890">
		<ul className="ps-v2-tools-nav__list">
			{ TOOLS_NAV.map( ( item ) => {
				const icon = TOOLS_NAV_ICONS[ item.slug ];
				return (
					<li key={ item.slug } className="ps-v2-tools-nav__item">
						<NavLink
							to={ `/tools/${ item.slug }` }
							className={ ( { isActive } ) =>
								`ps-v2-tools-nav__link${ isActive || item.slug === activeSlug ? ' is-active' : '' }`
							}
							data-figma-node={ item.figmaNode }
						>
							<span className="ps-v2-tools-nav__link-inner">
								{ icon && (
									<span className="ps-v2-tools-nav__icon" aria-hidden="true">
										<img src={ icon } alt="" width="20" height="20" />
									</span>
								) }
								<span className="ps-v2-tools-nav__label">{ item.label }</span>
							</span>
						</NavLink>
					</li>
				);
			} ) }
		</ul>
	</nav>
);

export default ToolsSubnav;
