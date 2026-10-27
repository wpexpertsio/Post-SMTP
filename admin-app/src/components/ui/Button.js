const VARIANT_CLASS = {
	primary: 'ps-btn--blue',
	secondary: 'ps-btn--outline',
	dark: 'ps-btn--dark',
	green: 'ps-btn--green',
	orange: 'ps-btn--orange',
	outlineLight: 'ps-btn--outline-light',
};

const Button = ( {
	variant = 'primary',
	className = '',
	href,
	children,
	...props
} ) => {
	const classes = `ps-btn ${ VARIANT_CLASS[ variant ] || VARIANT_CLASS.primary }${ className ? ` ${ className }` : '' }`;

	if ( href ) {
		return (
			<a href={ href } className={ classes } { ...props }>
				{ children }
			</a>
		);
	}

	return (
		<button type="button" className={ classes } { ...props }>
			{ children }
		</button>
	);
};

export default Button;
