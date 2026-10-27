const FieldInput = ( { field, fieldKey, values, onChange } ) => {
	const type = field.input_type || 'text';
	const value = values[ fieldKey ] ?? '';

	if ( type === 'select' && field.options ) {
		return (
			<select
				id={ `field-${ fieldKey }` }
				className="ps-v2-field-input"
				value={ value }
				onChange={ ( e ) => onChange( { ...values, [ fieldKey ]: e.target.value } ) }
			>
				{ Object.entries( field.options ).map( ( [ optValue, optLabel ] ) => (
					<option key={ optValue } value={ optValue }>{ optLabel }</option>
				) ) }
			</select>
		);
	}

	if ( type === 'oauth_button' ) {
		return null;
	}

	if ( type === 'checkbox' ) {
		return (
			<input
				id={ `field-${ fieldKey }` }
				type="checkbox"
				checked={ Boolean( value ) }
				onChange={ ( e ) => onChange( { ...values, [ fieldKey ]: e.target.checked } ) }
			/>
		);
	}

	const inputType = type === 'password' ? 'password' : type === 'email' ? 'email' : type === 'number' ? 'number' : 'text';

	return (
		<input
			id={ `field-${ fieldKey }` }
			className="ps-v2-field-input"
			type={ inputType }
			value={ value }
			placeholder={ field.placeholder || ( field.encrypt ? '••••••••' : '' ) }
			autoComplete={ field.encrypt ? 'off' : undefined }
			onChange={ ( e ) => onChange( { ...values, [ fieldKey ]: e.target.value } ) }
		/>
	);
};

const FormFieldBlock = ( { fieldKey, field, values, onChange } ) => {
	if ( ! field || field.input_type === 'oauth_button' ) {
		return null;
	}

	if ( field.inline && field.input_type === 'checkbox' ) {
		return (
			<div className="ps-v2-form-field ps-v2-form-field--inline-check" key={ fieldKey }>
				<label className="ps-v2-form-field__check" htmlFor={ `field-${ fieldKey }` }>
					<FieldInput field={ field } fieldKey={ fieldKey } values={ values } onChange={ onChange } />
					<span>{ field.label || fieldKey }</span>
				</label>
			</div>
		);
	}

	return (
		<div className="ps-v2-form-field" key={ fieldKey }>
			<label htmlFor={ `field-${ fieldKey }` } className="ps-v2-form-field__label">
				{ field.label || fieldKey }
				{ field.required && <span className="ps-v2-required">*</span> }
			</label>
			{ field.description && (
				<p className="ps-v2-form-field__hint">{ field.description }</p>
			) }
			<FieldInput field={ field } fieldKey={ fieldKey } values={ values } onChange={ onChange } />
		</div>
	);
};

const FormGenerator = ( { schema, values, onChange } ) => {
	if ( ! schema?.fields ) {
		return null;
	}

	const sequence = schema.field_sequence || Object.keys( schema.fields );

	return (
		<div className="form-generator ps-v2-form-generator">
			{ sequence.map( ( key ) => {
				const field = schema.fields[ key ];
				return (
					<FormFieldBlock
						key={ key }
						fieldKey={ key }
						field={ field }
						values={ values }
						onChange={ onChange }
					/>
				);
			} ) }
		</div>
	);
};

export default FormGenerator;
