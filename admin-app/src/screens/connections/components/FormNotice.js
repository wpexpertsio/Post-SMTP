const FormNotice = ( { type = 'success', message, onDismiss } ) => {
	if ( ! message ) {
		return null;
	}
	return (
		<div
			className={ `ps-v2-form-notice ps-v2-form-notice--${ type }` }
			role={ type === 'error' ? 'alert' : 'status' }
		>
			<span>{ message }</span>
			{ onDismiss && (
				<button type="button" className="ps-v2-form-notice__dismiss" onClick={ onDismiss } aria-label="Dismiss">
					×
				</button>
			) }
		</div>
	);
};

export default FormNotice;
