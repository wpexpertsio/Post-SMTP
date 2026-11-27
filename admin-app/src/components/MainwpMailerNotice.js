import { getAdminConfig } from '../config/admin';

const MainwpMailerNotice = () => {
	const config = getAdminConfig();

	if ( ! config.mainwpChildUsesParent ) {
		return null;
	}

	const configuredCopy = config.mainwpParentConfigured
		? 'Outbound mail is sent through your MainWP dashboard site. Email Log on this site shows messages sent here.'
		: 'This site is linked to a MainWP parent, but the parent mailer is not configured yet. Ask your dashboard administrator to finish Post SMTP setup.';

	return (
		<div className="ps-v2-notice ps-v2-notice--info ps-multisite-notice ps-multisite-notice--info" role="status">
			<p>
				<strong>Connected to the MainWP parent site.</strong> { configuredCopy } Mailer settings cannot be
				changed on this site.
			</p>
		</div>
	);
};

export default MainwpMailerNotice;