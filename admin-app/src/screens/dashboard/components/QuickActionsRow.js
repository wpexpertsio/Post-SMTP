import Card from '../../../components/ui/Card';
import Badge from '../../../components/ui/Badge';
import Button from '../../../components/ui/Button';
import { providerIcon } from '../../../components/ui/Icons';
import { getAdminConfig, getAdminUrls } from '../../../config/admin';

const QuickActionsRow = () => {
	const config = getAdminConfig();
	const urls = getAdminUrls();

	return (
		<div className="ps-triple-grid">
			<Card className="ps-gmail-card">
				<div className="ps-gmail-card__badges">
					<Badge variant="pro">Pro</Badge>
					<span className="ps-mini-badge">60-second setup</span>
				</div>
				<div className="ps-gmail-card__body">
					<img src={ providerIcon( 'gmail-pro-feature.svg' ) } alt="" className="ps-gmail-card__logo" />
					<div>
						<h3>Connect Gmail in one click</h3>
						<p>Sign in with Google and Post SMTP does the rest.</p>
					</div>
				</div>
				<div className="ps-gmail-card__actions">
					<Button variant="primary" href={ `${ urls.wizard }&socket=gmail_api` }>
						Enable one-click →
					</Button>
					<a href={ urls.docs } className="ps-text-link" target="_blank" rel="noreferrer">Learn more</a>
				</div>
			</Card>

			<Card className="ps-test-card">
				<h3>Send test email</h3>
				<label className="ps-field">
					<span>Send to</span>
					<input
						type="email"
						defaultValue={ config.senderEmail || '' }
						placeholder="you@example.com"
						readOnly
					/>
				</label>
				<Button variant="primary" href={ urls.settings }>Send test</Button>
			</Card>

			<Card className="ps-reports-card">
				<div className="ps-reports-card__head">
					<h3>Reports &amp; tracking</h3>
					<Badge variant="pro">Pro</Badge>
				</div>
				<ul>
					<li>Sends &amp; failures</li>
					<li>Opens</li>
					<li>Per-mailer view</li>
				</ul>
				<p className="ps-reports-card__note">Used on 300,000+ WordPress sites.</p>
				<Button variant="orange" href={ urls.pro } target="_blank" rel="noreferrer">
					Upgrade to Pro
				</Button>
				<a href={ urls.pro } className="ps-text-link" target="_blank" rel="noreferrer">See what&apos;s included</a>
			</Card>
		</div>
	);
};

export default QuickActionsRow;
