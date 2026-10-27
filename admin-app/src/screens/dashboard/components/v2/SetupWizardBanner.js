import wizardWandIcon from '../../../../assets/dashboard/wizard-wand.svg';
import { getAdminUrls } from '../../../../config/admin';

/**
 * Figma: Post-SMTP-Dashboard-Revamp — node 672-4924
 */
const SetupWizardBanner = () => {
	const urls = getAdminUrls();

	return (
		<section className="ps-v2-wizard-banner" data-figma-node="672:4924">
			<div className="ps-v2-wizard-banner__left">
				<div className="ps-v2-wizard-banner__icon" aria-hidden="true">
					<img src={ wizardWandIcon } alt="" width="24" height="24" />
				</div>
				<div className="ps-v2-wizard-banner__text">
					<strong>Run setup wizard and start sending email</strong>
					<p>Pick a mailer, connect it, send a test email.</p>
				</div>
			</div>
			<a href={ urls.wizard } className="ps-v2-wizard-banner__cta">
				Open Setup Wizard
			</a>
		</section>
	);
};

export default SetupWizardBanner;
