import { CHANNEL_PAGE_HEADERS } from '../notificationChannelAssets';
import { proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';

const NotificationsChannelPageHeader = ( { slug } ) => {
	const meta = CHANNEL_PAGE_HEADERS[ slug ] || CHANNEL_PAGE_HEADERS.admin_email;

	return (
		<header className="ps-v2-notify-page-header" data-figma-node="672:7985">
			{ meta.icon && (
				<div
					className={
						meta.iconFramed
							? 'ps-v2-notify-page-header__icon ps-v2-notify-page-header__icon--framed'
							: 'ps-v2-notify-page-header__icon'
					}
					aria-hidden="true"
				>
					<img src={ meta.icon } alt="" width={ meta.iconFramed ? 28 : 50 } height={ meta.iconFramed ? 28 : 50 } />
				</div>
			) }
			<div className="ps-v2-notify-page-header__copy">
				<div className="ps-v2-notify-page-header__title-row">
					<h1 className="ps-v2-notify-page-header__title">{ meta.title }</h1>
					{ meta.showProBadge && (
						<span className="ps-v2-notify-pro-tag">
							<img src={ proCrownIcon } alt="" width="12" height="10" />
							PRO
						</span>
					) }
				</div>
				<p className="ps-v2-notify-page-header__sub">{ meta.subtitle }</p>
			</div>
		</header>
	);
};

export default NotificationsChannelPageHeader;
