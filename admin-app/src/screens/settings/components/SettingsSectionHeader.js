const SettingsSectionHeader = ( { icon, title, description, figmaNode } ) => (
	<header className="ps-v2-settings-section-head" data-figma-node={ figmaNode }>
		{ icon && (
			<span className="ps-v2-settings-section-head__icon" aria-hidden="true">
				{ icon }
			</span>
		) }
		<div className="ps-v2-settings-section-head__copy">
			<h2 className="ps-v2-settings-section-head__title">{ title }</h2>
			{ description && <p className="ps-v2-settings-section-head__desc">{ description }</p> }
		</div>
	</header>
);

export default SettingsSectionHeader;
