import iconChevronDown from '../../../assets/tools/icon-chevron-down.svg';

const SettingsSelectField = ( { label, hint, value, onChange, options = [], disabled = false } ) => (
	<SettingsField label={ label } hint={ hint }>
		<div className="ps-v2-settings-select-wrap">
			<label
				className={ `ps-v2-settings-select${ disabled ? ' is-disabled' : '' }` }
			>
				<span className="ps-v2-settings-select__face">
					{ options.find( ( opt ) => String( opt.value ) === String( value ) )?.label ?? value }
				</span>
				<img
					className="ps-v2-settings-select__chevron"
					src={ iconChevronDown }
					alt=""
					width="10"
					height="6"
				/>
				<select
					className="ps-v2-settings-select__native"
					value={ value }
					disabled={ disabled }
					onChange={ ( e ) => onChange( e.target.value ) }
					aria-label={ label }
				>
					{ options.map( ( opt ) => (
						<option key={ opt.value } value={ opt.value }>
							{ opt.label }
						</option>
					) ) }
				</select>
			</label>
		</div>
	</SettingsField>
);

const SettingsField = ( { label, hint, children } ) => (
	<div className="ps-v2-settings-field">
		<div className="ps-v2-settings-field__head">
			<span className="ps-v2-settings-field__label">{ label }</span>
			{ hint && <p className="ps-v2-settings-field__hint">{ hint }</p> }
		</div>
		{ children }
	</div>
);

export default SettingsSelectField;
