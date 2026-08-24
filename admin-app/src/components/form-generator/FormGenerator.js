import { useState } from '@wordpress/element';

const FormGenerator = ( { schema, values, onChange } ) => {
	if ( ! schema?.fields ) {
		return null;
	}

	const sequence = schema.field_sequence || Object.keys( schema.fields );

	return (
		<div className="form-generator">
			{ sequence.map( ( key ) => {
				const field = schema.fields[ key ];
				if ( ! field ) {
					return null;
				}
				return (
					<div className="form-generator-field" key={ key }>
						<label htmlFor={ `field-${ key }` }>{ field.label || key }</label>
						<input
							id={ `field-${ key }` }
							type={ field.input_type || 'text' }
							value={ values[ key ] || '' }
							placeholder={ field.placeholder || '' }
							onChange={ ( e ) => onChange( { ...values, [ key ]: e.target.value } ) }
						/>
					</div>
				);
			} ) }
		</div>
	);
};

export default FormGenerator;
