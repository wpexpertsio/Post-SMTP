import { useMemo } from '@wordpress/element';
import MailerCard from '../../connections/components/MailerCard';
import { buildWizardMailerGrid } from '../mailerGridLayoutWizard';

/**
 * Figma 693:20457 — full grid, wizard-scoped styling only (`.ps-setup-wizard__mailer-grid`).
 */
const WizardMailerGrid = ( { items, onSelect, onRestrictedClick } ) => {
	const rows = useMemo( () => buildWizardMailerGrid( items ), [ items ] );

	return (
		<div className="ps-setup-wizard__mailer-grid ps-v2-mailer-choose" data-figma-node="1000:19164">
			<div className="ps-v2-mailer-choose__rows">
				{ rows.map( ( row, rowIndex ) => (
					<div
						key={ rowIndex }
						className={ `ps-v2-mailer-choose__row${ row.length > 3 ? ' ps-setup-wizard__mailer-row--quad' : '' }` }
					>
						{ row.map( ( item ) => (
							<MailerCard
								key={ item.slug }
								item={ item }
								selected={ false }
								onSelect={ onSelect }
								onRestrictedClick={ onRestrictedClick }
							/>
						) ) }
					</div>
				) ) }
			</div>
		</div>
	);
};

export default WizardMailerGrid;