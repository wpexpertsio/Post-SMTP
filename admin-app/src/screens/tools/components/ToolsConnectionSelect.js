import { Link } from 'react-router-dom';
import iconChevronDown from '../../../assets/tools/icon-chevron-down.svg';
import { connectionDisplayLabel } from '../connectionDisplayLabel';

const ToolsConnectionSelect = ( {
	connections = [],
	value,
	onChange,
	invalid = false,
	selectedConnection,
} ) => {
	const display = connectionDisplayLabel( selectedConnection ) || 'Select connection';

	return (
		<div className="ps-v2-tools-conn-select-wrap">
			<label
				className={ `ps-v2-tools-conn-select${ invalid ? ' is-invalid' : '' }` }
				data-figma-node="985:17967"
			>
				<span className="ps-v2-tools-conn-select__face" aria-hidden="true">
					<span className="ps-v2-tools-conn-select__text">{ display }</span>
					{ selectedConnection?.is_primary && (
						<span className="ps-v2-tools-role-badge">PRIMARY</span>
					) }
					{ selectedConnection?.role === 'fallback' && selectedConnection?.role_label && (
						<span className="ps-v2-tools-role-badge ps-v2-tools-role-badge--fallback">
							{ selectedConnection.role_label.toUpperCase() }
						</span>
					) }
				</span>
				<img
					className="ps-v2-tools-conn-select__chevron"
					src={ iconChevronDown }
					alt=""
					width="10"
					height="6"
				/>
				<select
					className="ps-v2-tools-conn-select__native"
					value={ value }
					onChange={ ( e ) => onChange( parseInt( e.target.value, 10 ) ) }
					aria-label="Connection"
					aria-invalid={ invalid ? 'true' : undefined }
				>
					{ connections.map( ( conn ) => {
						const face = connectionDisplayLabel( conn );
						const suffix =
							conn.label && conn.label !== face && conn.label !== conn.mailer_label
								? ` — ${ conn.label }`
								: '';
						return (
							<option key={ conn.id } value={ conn.id } disabled={ ! conn.is_usable }>
								{ face }{ suffix }
							</option>
						);
					} ) }
				</select>
			</label>
			<p className="ps-v2-tools-field-foot" data-figma-node="985:17973">
				Add a Fallback Connection in Connections to test your backup mailer here.{' '}
				<Link to="/connections">Open Connections</Link>
			</p>
		</div>
	);
};

export default ToolsConnectionSelect;
