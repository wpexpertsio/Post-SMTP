const FallbackToggle = ( { checked, disabled, onChange, label = 'Enable as Fallback' } ) => (
	<label className="ps-v2-conn-fallback-toggle">
		<input
			type="checkbox"
			checked={ checked }
			disabled={ disabled }
			onChange={ ( e ) => onChange( e.target.checked ) }
		/>
		<span>{ label }</span>
	</label>
);

export default FallbackToggle;
