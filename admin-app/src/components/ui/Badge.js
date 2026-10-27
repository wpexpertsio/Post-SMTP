const Badge = ( { variant = 'default', className = '', children } ) => {
	const variantClass = variant === 'pro' ? ' is-pro' : '';
	return (
		<span className={ `ps-tier-badge${ variantClass }${ className ? ` ${ className }` : '' }` }>
			{ children }
		</span>
	);
};

export default Badge;
