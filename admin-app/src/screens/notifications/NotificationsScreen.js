import { Navigate, useParams } from 'react-router-dom';
import { getAdminConfig, getAdminUrls } from '../../config/admin';
import iconUpgradeArrow from '../../assets/help-docs/icon-upgrade-arrow.svg';
import useNotifications from './hooks/useNotifications';
import NotificationsChannelNav from './components/NotificationsChannelNav';
import NotificationsChannelPageHeader from './components/NotificationsChannelPageHeader';
import AdminEmailChannelPanel from './components/AdminEmailChannelPanel';
import NotificationsSetupSidebar from './components/NotificationsSetupSidebar';
import NotificationsChromeInstallAside from './components/NotificationsChromeInstallAside';
import NotificationsMobileAppAside from './components/NotificationsMobileAppAside';
import NotificationsGmailPromoAside from './components/NotificationsGmailPromoAside';
import NotificationsProChannelsAside from './components/NotificationsProChannelsAside';
import ChromeChannelPanel from './components/ChromeChannelPanel';
import WebhookChannelPanel from './components/WebhookChannelPanel';
import PushoverChannelPanel from './components/PushoverChannelPanel';
import SlackChannelPanel from './components/SlackChannelPanel';
import TeamsChannelPanel from './components/TeamsChannelPanel';
import TwilioChannelPanel from './components/TwilioChannelPanel';

const DEFAULT_SLUG = 'admin_email';
const PRO_ONLY_SLUGS = new Set( [ 'teams', 'twilio' ] );

const NotificationsScreen = () => {
	const config = getAdminConfig();
	const urls = getAdminUrls();
	const query = useNotifications();
	const data = query.data;
	const channels = data?.channels || [];
	const channelSettings = data?.channel_settings || {};
	const { channelSlug = DEFAULT_SLUG } = useParams();
	const activeSlug = channelSlug || DEFAULT_SLUG;
	const isPro = Boolean( data?.is_pro || config.isPro );
	const docUrl = data?.docs?.[ activeSlug ] || data?.docs?.admin_email;
	const activeSettings = channelSettings[ activeSlug ] || {};
	const panelLocked = Boolean( activeSettings.panel_locked );

	const onSaved = () => query.refetch();

	if ( query.isLoading ) {
		return <p className="ps-v2-notify-loading">Loading notifications…</p>;
	}

	if ( query.isError ) {
		return <p className="ps-v2-notify-loading">Could not load notification settings.</p>;
	}

	if ( PRO_ONLY_SLUGS.has( activeSlug ) && ! channels.some( ( c ) => c.slug === activeSlug ) ) {
		return <Navigate to="/notifications/admin_email" replace />;
	}

	const renderPanel = () => {
		const settings = channelSettings[ activeSlug ] || {};
		switch ( activeSlug ) {
			case 'admin_email':
				return (
					<AdminEmailChannelPanel
						adminEmail={ data?.admin_email || settings }
						wpAdminEmail={ data?.admin_email?.wp_admin_email }
						onSaved={ onSaved }
					/>
				);
			case 'chrome':
				return <ChromeChannelPanel settings={ settings } onSaved={ onSaved } />;
			case 'slack':
				return <SlackChannelPanel settings={ settings } onSaved={ onSaved } />;
			case 'pushover':
				return <PushoverChannelPanel settings={ settings } onSaved={ onSaved } />;
			case 'webhook':
				return <WebhookChannelPanel settings={ settings } onSaved={ onSaved } />;
			case 'teams':
				return <TeamsChannelPanel settings={ settings } onSaved={ onSaved } />;
			case 'twilio':
				return <TwilioChannelPanel settings={ settings } onSaved={ onSaved } />;
			default:
				return null;
		}
	};

	const showProChannelPromos =
		! isPro &&
		activeSlug !== 'chrome' &&
		activeSlug !== 'pushover' &&
		activeSlug !== 'teams' &&
		activeSlug !== 'twilio';

	const showLockedUpgradeBar =
		panelLocked &&
		( activeSlug === 'teams' || activeSlug === 'twilio' ) &&
		( activeSettings.upgrade_url || urls.pro );

	const upgradeUrl = activeSettings.upgrade_url || urls.pro;
	const upgradeLabel = activeSettings.upgrade_label || 'Upgrade to Pro';

	const chromeExtensionUrl =
		channelSettings.chrome?.chrome_extension_url ||
		'https://chrome.google.com/webstore/detail/npklmbkpbknkmbohdbpikeidiaekjoch';

	return (
		<div className="ps-v2-notify-page" data-figma-node="672:7917">
			<div className="ps-v2-notify-layout">
				<aside className="ps-v2-notify-layout__nav">
					<NotificationsChannelNav channels={ channels } activeSlug={ activeSlug } isPro={ isPro } />
				</aside>
				<div className="ps-v2-notify-layout__content">
					<NotificationsChannelPageHeader
						slug={ activeSlug }
						isPro={ isPro }
						panelLocked={ panelLocked }
					/>
					<div className="ps-v2-notify-content-row">
						<div className="ps-v2-notify-layout__main">
							{ renderPanel() }
							{ showLockedUpgradeBar && (
								<div className="ps-v2-notify-upgrade-bar" data-figma-node="1007:24774">
									<a
										href={ upgradeUrl }
										className="ps-v2-notify-upgrade-bar__cta"
										target="_blank"
										rel="noreferrer"
									>
										{ upgradeLabel }
										<img src={ iconUpgradeArrow } alt="" width="14" height="10" />
									</a>
								</div>
							) }
						</div>
						<aside className="ps-v2-notify-layout__aside">
							{ activeSlug === 'chrome' && (
								<NotificationsChromeInstallAside installUrl={ chromeExtensionUrl } />
							) }
							{ activeSlug === 'pushover' && <NotificationsMobileAppAside /> }
							{ ( activeSlug === 'chrome' || activeSlug === 'pushover' ) && ! isPro && (
								<NotificationsGmailPromoAside />
							) }
							{ showProChannelPromos && (
								<NotificationsProChannelsAside promos={ data?.pro_channel_promos } />
							) }
							<NotificationsSetupSidebar slug={ activeSlug } docUrl={ docUrl } />
						</aside>
					</div>
				</div>
			</div>
		</div>
	);
};

export default NotificationsScreen;
