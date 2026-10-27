const Card = ( { className = '', children, as = 'section', ...props } ) => {
	const Tag = as;
	return (
		<Tag className={ `ps-card${ className ? ` ${ className }` : '' }` } { ...props }>
			{ children }
		</Tag>
	);
};

export const CardHeader = ( { title, action, subtitle } ) => (
	<>
		<div className="ps-card__head">
			<h2>{ title }</h2>
			{ action }
		</div>
		{ subtitle && <p className="ps-card__sub">{ subtitle }</p> }
	</>
);

export default Card;
