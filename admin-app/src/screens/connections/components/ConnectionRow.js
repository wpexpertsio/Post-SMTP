import { Link } from 'react-router-dom';
import MailerAvatar from '../../dashboard/components/MailerAvatar';
import { connectionLabel } from '../../../utils/providers';
import { connectionSubtitle } from '../model/connectionSubtitle';
import ConnectionStatusToggle from './ConnectionStatusToggle';
import { iconDelete, iconDrag, iconEdit } from './connectionRowAssets';

const ConnectionRow = ( {
	connection,
	index,
	isPrimary,
	onToggleFallback,
	togglePending,
	onDelete,
	dragHandleProps,
} ) => {
	const role = connection.role || ( isPrimary ? 'primary' : connection.fallback_enabled ? 'fallback' : 'inactive' );
	const subtitle = connectionSubtitle( connection );

	return (
		<li className="ps-v2-conn-row">
			<button
				type="button"
				className="ps-v2-conn-row__handle"
				aria-label="Drag to reorder"
				{ ...dragHandleProps }
			>
				<img src={ iconDrag } alt="" width="8" height="15" />
			</button>
			<div className="ps-v2-conn-row__avatar">
				<MailerAvatar provider={ connection.provider } />
			</div>
			<div className="ps-v2-conn-row__meta">
				<strong className="ps-v2-conn-row__title">{ connectionLabel( connection ) }</strong>
				{ subtitle ? <span className="ps-v2-conn-row__subtitle">{ subtitle }</span> : null }
			</div>
			<div className="ps-v2-conn-row__controls">
				<ConnectionStatusToggle
					role={ role }
					checked={ isPrimary ? true : Boolean( connection.fallback_enabled ) }
					disabled={ togglePending }
					onChange={ ( enabled ) => onToggleFallback( index, enabled ) }
				/>
				<Link
					to={ `/connections/${ index }/edit` }
					className="ps-v2-conn-row__icon-btn"
					aria-label="Edit connection"
				>
					<img src={ iconEdit } alt="" width="35" height="35" />
				</Link>
				<button
					type="button"
					className="ps-v2-conn-row__icon-btn ps-v2-conn-row__icon-btn--danger"
					aria-label="Delete connection"
					onClick={ () => onDelete( index ) }
				>
					<img src={ iconDelete } alt="" width="13" height="15" />
				</button>
			</div>
		</li>
	);
};

export default ConnectionRow;
