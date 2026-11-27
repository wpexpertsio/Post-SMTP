import { getAdminConfig } from '../config/admin';

const MultisiteMailerNotice = () => {
	const config = getAdminConfig();

	if ( config.mainwpChildUsesParent ) {
		return null;
	}

	if ( ! config.isMultisite ) {
		return null;
	}

	if ( config.syncedWithMain && config.multisiteGlobalSettings ) {
		return (
			<div className="ps-v2-notice ps-v2-notice--info ps-multisite-notice ps-multisite-notice--info" role="status">
				<p>
					<strong>Configured with the main site.</strong> Mailer settings on this site were copied from the network main site.
					Email Log shows messages sent from this site only—not from the main site or other subsites.
				</p>
			</div>
		);
	}

	if ( config.mailerConfigLocked ) {
		return (
			<div className="ps-v2-notice ps-v2-notice--warning ps-multisite-notice" role="status">
				<p>
					<strong>Network-managed mailer.</strong> Mailer settings are controlled on the main site. Ask a network administrator to run Sync sites under Network Settings.
				</p>
			</div>
		);
	}

	return null;
};

export default MultisiteMailerNotice;