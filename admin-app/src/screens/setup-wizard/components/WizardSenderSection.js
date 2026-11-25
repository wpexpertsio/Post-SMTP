import iconForceSenderShield from '../../../assets/setup-wizard/icon-force-sender-shield.svg';

const forceSenderChecked = ( values ) =>
	Boolean( values?.prevent_sender_name_override ) || Boolean( values?.prevent_sender_email_override );

export const WizardForceSenderRow = ( { values, onChange } ) => {
	const checked = forceSenderChecked( values );

	const handleToggle = () => {
		const next = ! checked;
		onChange( {
			...values,
			prevent_sender_name_override: next,
			prevent_sender_email_override: next,
		} );
	};

	return (
		<div className="ps-setup-wizard__force-sender" data-figma-node="693:21795">
			<div className="ps-setup-wizard__force-sender-main">
				<div className="ps-setup-wizard__force-sender-icon-wrap" data-figma-node="693:21797">
					<img
						className="ps-setup-wizard__force-sender-icon"
						src={ iconForceSenderShield }
						alt=""
						width="19"
						height="22"
						aria-hidden="true"
					/>
				</div>
				<div className="ps-setup-wizard__force-sender-copy">
					<p className="ps-setup-wizard__force-sender-title">Force these sender details on every email</p>
					<p className="ps-setup-wizard__force-sender-desc">Overrides From name/address set by other plugins.</p>
				</div>
			</div>
			<div className={ `ps-v2-conn-status${ checked ? ' is-on' : '' }` }>
				<button
					type="button"
					className="ps-v2-conn-status__switch"
					role="switch"
					aria-checked={ checked }
					aria-label="Force these sender details on every email"
					onClick={ handleToggle }
				>
					<span className="ps-v2-conn-status__knob" aria-hidden="true" />
				</button>
			</div>
		</div>
	);
};

export const renderConfigureSenderFields = ( { isWizardLayout, renderField, values, onChange } ) => {
	if ( isWizardLayout ) {
		return (
			<>
				{ renderField( 'from_name' ) }
				{ renderField( 'from_email' ) }
				<WizardForceSenderRow values={ values } onChange={ onChange } />
			</>
		);
	}

	return (
		<>
			<div className="ps-v2-configure-form__group">
				{ renderField( 'from_name' ) }
				{ renderField( 'prevent_sender_name_override' ) }
			</div>
			<div className="ps-v2-configure-form__group">
				{ renderField( 'from_email' ) }
				{ renderField( 'prevent_sender_email_override' ) }
			</div>
		</>
	);
};