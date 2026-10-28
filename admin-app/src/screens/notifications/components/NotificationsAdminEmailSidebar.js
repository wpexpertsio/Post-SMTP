import iconExternalLink from '../../../assets/dashboard/connections/helper/icon-external-link.svg';

const NotificationsAdminEmailSidebar = ( { docUrl } ) => (
	<div className="ps-v2-notify-aside__stack">
		<aside className="ps-v2-conn-helper-card" data-figma-node="672:7890-docs">
			<div className="ps-v2-conn-helper-card__copy">
				<strong className="ps-v2-conn-helper-card__title">Set Up Admin Email Failure Alerts</strong>
				<p>
					Get notified directly in your WordPress administrator inbox when Post SMTP encounters email
					delivery failures.
				</p>
			</div>
			<a
				href={ docUrl }
				className="ps-v2-conn-helper-card__doc-link"
				target="_blank"
				rel="noreferrer"
			>
				<span>View Admin Email Setup Docs</span>
				<img src={ iconExternalLink } alt="" width="13" height="13" />
			</a>
		</aside>
	</div>
);

export default NotificationsAdminEmailSidebar;
