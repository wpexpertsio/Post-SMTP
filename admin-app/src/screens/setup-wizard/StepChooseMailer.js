import { getAdminUrls, getAdminConfig } from '../../config/admin';
import MailerGrid from '../connections/components/MailerGrid';
import { useSetupWizardContext } from './SetupWizardContext';

const StepChooseMailer = () => {
	const { selectProvider, bootstrap } = useSetupWizardContext();
	const urls = getAdminUrls();
	const items = bootstrap?.catalog?.items || [];
	const guideUrl = urls.docs || 'https://postmansmtp.com/documentation/';

	const handleRestricted = () => {
		const proUrl = urls.pro || 'https://postmansmtp.com/pricing/';
		window.open( proUrl, '_blank', 'noopener,noreferrer' );
	};

	return (
		<section className="ps-setup-wizard__panel" data-figma-node="693:20458">
			<header className="ps-setup-wizard__panel-head">
				<h1>Choose a mailer</h1>
				<p className="ps-setup-wizard__panel-lead">
					Not sure which one?{' '}
					<a href={ guideUrl } target="_blank" rel="noreferrer">
						Read the mailer guide
					</a>
				</p>
			</header>

			<MailerGrid
				items={ items }
				selectedSlug={ null }
				onSelect={ selectProvider }
				onRestrictedClick={ ! getAdminConfig().isPro ? handleRestricted : undefined }
			/>
		</section>
	);
};

export default StepChooseMailer;