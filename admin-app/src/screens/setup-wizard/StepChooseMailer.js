import { getAdminUrls, getAdminConfig } from '../../config/admin';
import WizardMailerGrid from './components/WizardMailerGrid';
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
		<article className="ps-setup-wizard__card ps-setup-wizard__card--step-1" data-figma-node="693:20535">
			<header className="ps-setup-wizard__step-intro" data-figma-node="693:20536">
				<div className="ps-setup-wizard__step-kicker">STEP 1</div>
				<h1 className="ps-setup-wizard__step-title">Choose a Mailer</h1>
				<p className="ps-setup-wizard__step-lead">
					Not sure which one? Read the{' '}
					<a href={ guideUrl } target="_blank" rel="noreferrer">
						mailer guide
					</a>
				</p>
			</header>

			<WizardMailerGrid
				items={ items }
				onSelect={ selectProvider }
				onRestrictedClick={ ! getAdminConfig().isPro ? handleRestricted : undefined }
			/>
		</article>
	);
};

export default StepChooseMailer;