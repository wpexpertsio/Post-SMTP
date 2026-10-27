import { getStatusBadge } from '../model/emailLogStatus';

const EmailLogStatusBadge = ( { status } ) => {
	const badge = getStatusBadge( status );
	return (
		<span className={ `ps-el-status ${ badge.className }` }>
			<img className="ps-el-status__icon" src={ badge.icon } alt="" width="12" height="12" />
			{ badge.label }
		</span>
	);
};

export default EmailLogStatusBadge;
