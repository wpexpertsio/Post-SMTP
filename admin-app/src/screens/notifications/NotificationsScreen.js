import { useParams } from 'react-router-dom';
import { getAdminConfig } from '../../config/admin';
import useNotifications from './hooks/useNotifications';
import NotificationsChannelNav from './components/NotificationsChannelNav';
import AdminEmailChannelPanel from './components/AdminEmailChannelPanel';
import NotificationsSetupSidebar from './components/NotificationsSetupSidebar';
import NotificationsProSidebar from './components/NotificationsProSidebar';
import {
	ChromeChannelPanel,
	PushoverChannelPanel,
	SlackChannelPanel,
	TeamsChannelPanel,
	TwilioChannelPanel,
	WebhookChannelPanel,
} from './components/NotificationChannelPanels';

const DEFAULT_SLUG = 'admin_email';

const NotificationsScreen = () => {
	const config = getAdminConfig();
	const query = useNotifications();
	const data = query.data;
	const channels = data?.channels || [];
	const channelSettings = data?.channel_settings || {};
	const { channelSlug = DEFAULT_SLUG } = useParams();
	const activeSlug = channelSlug || DEFAULT_SLUG;
	const isPro = Boolean( data?.is_pro || config.isPro );
	const docUrl = data?.docs?.[ activeSlug ] || data?.docs?.admin_email;
	const channelMeta = channels.find( ( item ) => item.slug === activeSlug );

	const onSaved = () => query.refetch();

	if ( query.isLoading ) {
		return <p className="ps-v2-notify-loading">Loading notifications…</p>;
	}

	if ( query.isError ) {
		return <p className="ps-v2-notify-loading">Could not load notification settings.</p>;
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
				return <TeamsChannelPanel settings={ settings } channel={ channelMeta } onSaved={ onSaved } />;
			case 'twilio':
				return <TwilioChannelPanel settings={ settings } channel={ channelMeta } onSaved={ onSaved } />;
			default:
				return null;
		}
	};

	return (
		<div className="ps-v2-notify-page" data-figma-node="672:7890">
			<div className="ps-v2-notify-layout">
				<aside className="ps-v2-notify-layout__nav">
					<NotificationsChannelNav channels={ channels } activeSlug={ activeSlug } />
				</aside>
				<div className="ps-v2-notify-layout__main">{ renderPanel() }</div>
				<aside className="ps-v2-notify-layout__aside">
					<NotificationsSetupSidebar slug={ activeSlug } docUrl={ docUrl } />
					{ ! isPro && activeSlug === 'admin_email' && <NotificationsProSidebar /> }
				</aside>
			</div>
		</div>
	);
};

export default NotificationsScreen;
