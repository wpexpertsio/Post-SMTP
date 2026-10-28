import { getAdminConfig } from '../../../config/admin';
import { mailerConnectionIcon } from '../../connections/model/mailerConnectionIcons';
import { providerIconSlug, providerInitial } from '../../../utils/providers';

const MailerAvatar = ( { provider } ) => {
	const bundledIcon = mailerConnectionIcon( provider );
	const config = getAdminConfig();
	const slug = providerIconSlug( provider );
	const assets = config.assetsUrl || '';

	if ( bundledIcon ) {
		return (
			<div className="ps-mailer__avatar ps-mailer__avatar--image">
				<img src={ bundledIcon } alt="" />
			</div>
		);
	}

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
