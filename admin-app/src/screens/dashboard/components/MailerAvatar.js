import { getAdminConfig } from '../../../config/admin';
import { providerIconSlug, providerInitial } from '../../../utils/providers';

const MailerAvatar = ( { provider } ) => {
	const config = getAdminConfig();
	const slug = providerIconSlug( provider );
	const assets = config.assetsUrl || '';

	if ( slug && assets ) {
		return (
			<div className="ps-mailer__avatar ps-mailer__avatar--image">
				<img src={ `${ assets }${ slug }` } alt="" />
			</div>
		);
	}

	return <div className="ps-mailer__avatar">{ providerInitial( provider ) }</div>;
};

export default MailerAvatar;
