import { iconExternalLink, iconSetupHelperTag } from '../../connections/components/connectionHelperAssets';

const DOC_COPY = {
	admin_email: {
		title: 'What is Admin Email?',
		description: 'Reliable sending for busy sites.',
		cta: 'View documentation',
	},
	chrome: {
		title: 'What is Chrome Extension?',
		description: 'Reliable sending for busy sites.',
		cta: 'View documentation',
	},
	slack: {
		title: 'What is Slack?',
		description: 'Reliable sending for busy sites.',
		cta: 'View documentation',
	},
	pushover: {
		title: 'What is Pushover?',
		description: 'Reliable sending for busy sites.',
		cta: 'View documentation',
	},
	webhook: {
		title: 'What is Webhook?',
		description: 'Reliable sending for busy sites.',
		cta: 'View documentation',
	},
};

const NotificationsSetupSidebar = ( { slug, docUrl } ) => {
	const copy = DOC_COPY[ slug ] || DOC_COPY.admin_email;
	const isAdminEmail = slug === 'admin_email';
	const isChrome = slug === 'chrome';
	const isWebhook = slug === 'webhook';
	const isPushover = slug === 'pushover';
	const isSlack = slug === 'slack';

	const useFigmaHelperTitle = isAdminEmail || isChrome || isWebhook || isPushover || isSlack;

	return (
		<aside className="ps-v2-conn-helper-card ps-v2-notify-helper-card" data-figma-node="1273:18215">
			<div className="ps-v2-conn-helper-card__tag">
				<span className="ps-v2-conn-helper-card__tag-icon" aria-hidden="true">
					<img src={ iconSetupHelperTag } alt="" width="13" height="14" />
				</span>
				<span className="ps-v2-conn-helper-card__tag-label">Setup helper</span>
			</div>
			<div className="ps-v2-conn-helper-card__content">
				<div className="ps-v2-conn-helper-card__main">
					<div className="ps-v2-conn-helper-card__copy">
						<strong
							className={
								useFigmaHelperTitle
									? 'ps-v2-notify-helper-card__title'
									: 'ps-v2-conn-helper-card__title'
							}
						>
							{ copy.title }
						</strong>
						<p>{ copy.description }</p>
					</div>
					{ docUrl && (
						<a href={ docUrl } className="ps-v2-conn-helper-card__doc-link" target="_blank" rel="noreferrer">
							<span>{ copy.cta }</span>
							<img src={ iconExternalLink } alt="" width="13" height="13" />
						</a>
					) }
				</div>
			</div>
		</aside>
	);
};

export default NotificationsSetupSidebar;
