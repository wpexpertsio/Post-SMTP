import { Link } from 'react-router-dom';
import Button from '../../../components/ui/Button';
import { IconCheck } from '../../../components/ui/Icons';
import { getAdminUrls } from '../../../config/admin';

const SetupStatusCard = ( { isConfigured } ) => {
	const urls = getAdminUrls();

	return (
		<section className={ `ps-setup-card${ isConfigured ? ' is-ready' : ' is-pending' }` }>
			<div className="ps-setup-card__icon" aria-hidden="true">
				<IconCheck />
			</div>
			<div className="ps-setup-card__body">
				<span className="ps-setup-card__eyebrow">Getting started</span>
				{ isConfigured ? (
					<>
						<h2>Setup complete</h2>
						<div className="ps-setup-card__title-row">
							<p>Email is sending through Post SMTP</p>
							<span className="ps-pill-active">Active</span>
						</div>
						<p className="ps-setup-card__hint">
							Change your mailer anytime in Connections or the Setup Wizard.
						</p>
					</>
				) : (
					<>
						<h2>Setup required</h2>
						<div className="ps-setup-card__title-row">
							<p>Post SMTP is not configured yet</p>
						</div>
						<p className="ps-setup-card__hint">
							Post SMTP is mimicking out-of-the-box WordPress email delivery.
						</p>
					</>
				) }
			</div>
			<div className="ps-setup-card__actions">
				<Button variant="dark" href={ urls.wizard }>+ Open Setup Wizard</Button>
				<Link to="/connections" className="ps-btn ps-btn--outline">
					Manage connections
				</Link>
			</div>
		</section>
	);
};

export default SetupStatusCard;
