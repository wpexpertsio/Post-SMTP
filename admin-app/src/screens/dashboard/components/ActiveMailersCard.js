import { Link } from 'react-router-dom';
import Card, { CardHeader } from '../../../components/ui/Card';
import QueryState from '../../../components/ui/QueryState';
import MailerAvatar from './MailerAvatar';
import { getAdminConfig } from '../../../config/admin';
import { formatTransportLabel } from '../../../utils/format';
import { connectionEmail, connectionLabel } from '../../../utils/providers';

const ActiveMailersCard = ( { connections, connectionsQuery } ) => {
	const config = getAdminConfig();
	const primary = connections[ 0 ];
	const fallbackTransport = config.transport || 'smtp';
	const legacyMailer = ! primary && config.senderEmail
		? {
			provider: fallbackTransport,
			title: formatTransportLabel( fallbackTransport ),
			sender_email: config.senderEmail,
		}
		: null;
	const displayMailer = primary || legacyMailer;

	return (
		<Card>
			<CardHeader
				title="Active mailers"
				subtitle="Your main sender and backup."
				action={ <Link to="/connections" className="ps-text-link">Manage</Link> }
			/>
			<QueryState
				isLoading={ connectionsQuery.isLoading }
				isError={ connectionsQuery.isError }
				error={ connectionsQuery.error }
			>
				{ displayMailer ? (
					<div className="ps-mailer">
						<MailerAvatar provider={ displayMailer.provider } />
						<div className="ps-mailer__info">
							<strong>{ connectionLabel( displayMailer, fallbackTransport ) }</strong>
							<span>{ connectionEmail( displayMailer, config.senderEmail ) }</span>
						</div>
						<span className="ps-mailer__tag">Primary</span>
					</div>
				) : (
					<div className="ps-mailer ps-mailer--empty">
						<MailerAvatar provider={ fallbackTransport } />
						<div className="ps-mailer__info">
							<strong>{ formatTransportLabel( fallbackTransport ) }</strong>
							<span>{ config.senderEmail || 'Configure your first mailer' }</span>
						</div>
					</div>
				) }
				<Link to="/connections" className="ps-mailer ps-mailer--ghost">
					<div className="ps-mailer__avatar">↻</div>
					<div className="ps-mailer__info">
						<strong>Add a fallback mailer</strong>
						<span>Auto-retry via a backup sender</span>
					</div>
					<span className="ps-mailer__tag is-muted">Fallback</span>
				</Link>
			</QueryState>
		</Card>
	);
};

export default ActiveMailersCard;
