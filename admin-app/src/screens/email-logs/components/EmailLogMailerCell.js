import { MAILER_CONNECTION_ICONS } from '../../connections/model/mailerConnectionIcons';
import smtpIcon from '../../../assets/dashboard/connections/mailers/other_smtp.svg';

const EmailLogMailerCell = ( { mailer, route } ) => {
	const icon = MAILER_CONNECTION_ICONS[ mailer ] || smtpIcon;
	const isFallback = route === 'fallback';
	const routeLabel = isFallback ? 'F' : 'P';

	return (
		<div className="ps-el-mailer">
			<span className="ps-el-mailer__icon-wrap">
				<img className="ps-el-mailer__icon" src={ icon } alt="" width="16" height="16" />
			</span>
			<span
				className={ `ps-el-mailer__route${ isFallback ? ' ps-el-mailer__route--fallback' : ' ps-el-mailer__route--primary' }` }
				title={ isFallback ? 'Fallback' : 'Primary' }
			>
				{ routeLabel }
			</span>
		</div>
	);
};

export default EmailLogMailerCell;
