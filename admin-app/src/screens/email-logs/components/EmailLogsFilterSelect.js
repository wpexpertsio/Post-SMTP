import { iconChevron } from './emailLogAssets';

const EmailLogsFilterSelect = ( { label, value, options, onChange, ariaLabel, compact } ) => {
	const current = options.find( ( opt ) => opt.value === value );
	const face = label
		? ( current ? `${ label }: ${ current.label }` : `${ label }: All` )
		: ( current?.label || value );

	return (
		<label className={ `ps-el-filter${ compact ? ' ps-el-filter--compact' : '' }` }>
			<span className="ps-el-filter__face" aria-hidden="true">{ face }</span>
			<img className="ps-el-filter__chevron" src={ iconChevron } alt="" width="10" height="6" />
			<select
				className="ps-el-filter__select"
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				aria-label={ ariaLabel || label }
			>
				{ options.map( ( opt ) => (
					<option key={ opt.value } value={ opt.value }>
						{ opt.label }
					</option>
				) ) }
			</select>
		</label>
	);
};

export default EmailLogsFilterSelect;
