import { MAILER_CONNECTION_ICONS, normalizeMailerSlug } from '../../connections/model/mailerConnectionIcons';
import smtpIcon from '../../../assets/dashboard/connections/mailers/other_smtp.svg';

const EmailLogMailerCell = ( { mailer, mailerLabel, route, routeLabel } ) => {
	const slug = normalizeMailerSlug( mailer ) || 'smtp';
	const icon = MAILER_CONNECTION_ICONS[ slug ] || smtpIcon;
	const isFallback = route === 'fallback';
	const routeName = routeLabel || ( isFallback ? 'Fallback' : 'Primary' );
	const mailerName = mailerLabel || mailer || 'Mailer';
	const tooltip = `${ routeName } — ${ mailerName }`;

	return (
		<div className="ps-el-mailer" title={ tooltip } aria-label={ tooltip }>
			<span className="ps-el-mailer__icon-wrap" aria-hidden="true">
				<img className="ps-el-mailer__icon" src={ icon } alt="" width="16" height="16" />
			</span>
			<span
				className={ `ps-el-mailer__route${ isFallback ? ' ps-el-mailer__route--fallback' : ' ps-el-mailer__route--primary' }` }
				title={ routeName }
				aria-label={ routeName }
			>
				{ isFallback ? 'F' : 'P' }
			</span>
		</div>
	);
};

export default EmailLogMailerCell;
