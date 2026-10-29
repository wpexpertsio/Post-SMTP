const SettingsToggleRow = ( { id, label, description, checked, onChange, disabled = false } ) => {
	const stacked = Boolean( description );

	return (
	<div
		className={
			stacked
				? 'ps-v2-settings-toggle-row ps-v2-settings-toggle-row--stacked'
				: 'ps-v2-settings-toggle-row'
		}
	>
		<label
			className={ `ps-v2-notify-switch${ disabled ? ' ps-v2-notify-switch--disabled' : '' }` }
			htmlFor={ id }
		>
			<input
				id={ id }
				type="checkbox"
				checked={ checked }
				disabled={ disabled }
				onChange={ ( e ) => onChange( e.target.checked ) }
			/>
			<span className="ps-v2-notify-switch__track" aria-hidden="true" />
		</label>
		{ stacked ? (
			<div className="ps-v2-settings-toggle-row__copy">
				<span className="ps-v2-settings-toggle-row__label">{ label }</span>
				<p className="ps-v2-settings-toggle-row__desc">{ description }</p>
			</div>
		) : (
			<span className="ps-v2-settings-toggle-row__label">{ label }</span>
		) }
	</div>
	);
};

export default SettingsToggleRow;
