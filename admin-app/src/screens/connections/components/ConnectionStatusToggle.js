const ROLE_BADGE = {
	primary: 'PRIMARY',
	fallback: 'FALLBACK',
	inactive: 'INACTIVE',
};

const ConnectionStatusToggle = ( {
	role,
	checked,
	disabled,
	onChange,
	title,
} ) => {
	const badge = ROLE_BADGE[ role ] || role?.toUpperCase() || '';
	const isOn = Boolean( checked );
	const interactive = role !== 'primary' && ! disabled;

	return (
		<div className={ `ps-v2-conn-status ${ isOn ? 'is-on' : 'is-off' }` } title={ title }>
			<span className={ `ps-v2-conn-status__badge ps-v2-conn-status__badge--${ role }` }>
				{ badge }
			</span>
			<button
				type="button"
				className="ps-v2-conn-status__switch"
				role="switch"
				aria-checked={ isOn }
				aria-label={ isOn ? 'On' : 'Off' }
				disabled={ ! interactive }
				onClick={ () => interactive && onChange( ! isOn ) }
			>
				<span className="ps-v2-conn-status__knob" aria-hidden="true" />
			</button>
		</div>
	);
};

export default ConnectionStatusToggle;
