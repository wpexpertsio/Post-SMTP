const SettingsSectionCard = ( { children, className = '', figmaNode } ) => (
	<section
		className={ `ps-v2-settings-section${ className ? ` ${ className }` : '' }` }
		data-figma-node={ figmaNode }
	>
		{ children }
	</section>
);

export default SettingsSectionCard;
