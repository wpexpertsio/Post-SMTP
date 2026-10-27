import { Link } from 'react-router-dom';
import QueryState from '../../../../components/ui/QueryState';
import MailerAvatar from '../MailerAvatar';
import { getAdminConfig } from '../../../../config/admin';
import { formatTransportLabel } from '../../../../utils/format';
import { connectionEmail, connectionLabel } from '../../../../utils/providers';

/**
 * Figma: Post-SMTP-Dashboard-Revamp — node 672-5114
 */
const ActiveMailersSidebar = ( { connections, connectionsQuery, hideCta = false, compactBadges = false } ) => {
	const config = getAdminConfig();
	const primary = connections[ 0 ];
	const fallback =
		connections.find( ( row, index ) => index > 0 && row.fallback_enabled ) ||
		connections.find( ( row, index ) => index > 0 && row.role === 'fallback' ) ||
		connections[ 1 ];
	const fallbackTransport = config.transport || 'smtp';

	const legacyPrimary = ! primary && config.senderEmail
		? {
			provider: fallbackTransport,
			title: formatTransportLabel( fallbackTransport ),
			sender_email: config.senderEmail,
		}
		: null;

	const displayPrimary = primary || legacyPrimary;

	return (
		<section
			className={ `ps-v2-mailers-card${ compactBadges ? ' ps-v2-mailers-card--compact' : '' }` }
			data-figma-node="672:5931"
		>
			<header className="ps-v2-mailers-card__head">
				<h3>Active mailers</h3>
				<Link to="/connections" className="ps-v2-mailers-card__manage">
					Manage
				</Link>
			</header>

			<QueryState
				isLoading={ connectionsQuery.isLoading }
				isError={ connectionsQuery.isError }
				error={ connectionsQuery.error }
			>
				<div className="ps-v2-mailers-card__body">
					{ displayPrimary && (
						<div className="ps-v2-mailer-tile">
							<div className="ps-v2-mailer-tile__avatar">
								<MailerAvatar provider={ displayPrimary.provider } />
							</div>
							<div className="ps-v2-mailer-tile__meta">
								<div className="ps-v2-mailer-tile__title-row">
									<strong>{ connectionLabel( displayPrimary, fallbackTransport ) }</strong>
									<span className="ps-v2-mailer-badge ps-v2-mailer-badge--primary">PRIMARY</span>
								</div>
								<span className="ps-v2-mailer-tile__sub">
									{ connectionEmail( displayPrimary, config.senderEmail ) }
								</span>
							</div>
						</div>
					) }

					{ fallback ? (
						<div className="ps-v2-mailer-tile">
							<div className="ps-v2-mailer-tile__avatar">
								<MailerAvatar provider={ fallback.provider } />
							</div>
							<div className="ps-v2-mailer-tile__meta">
								<div className="ps-v2-mailer-tile__title-row">
									<strong>{ connectionLabel( fallback, fallbackTransport ) }</strong>
									<span className="ps-v2-mailer-badge ps-v2-mailer-badge--fallback">FALLBACK</span>
								</div>
								<span className="ps-v2-mailer-tile__sub">Takes over if primary fails</span>
							</div>
						</div>
					) : (
						! displayPrimary && (
							<p className="ps-v2-mailers-card__hint">Add a fallback mailer for automatic retry.</p>
						)
					) }

					{ ! hideCta && (
						<Link to="/connections/add" className="ps-v2-mailers-card__cta">
							Add connection
						</Link>
					) }
				</div>
			</QueryState>
		</section>
	);
};

export default ActiveMailersSidebar;
