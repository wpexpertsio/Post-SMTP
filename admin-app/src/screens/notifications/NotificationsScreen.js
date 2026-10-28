import { Navigate, useParams } from 'react-router-dom';
import { getAdminConfig } from '../../config/admin';
import useNotifications from './hooks/useNotifications';
import NotificationsChannelNav from './components/NotificationsChannelNav';
import NotificationsChannelPageHeader from './components/NotificationsChannelPageHeader';
import AdminEmailChannelPanel from './components/AdminEmailChannelPanel';
import NotificationsSetupSidebar from './components/NotificationsSetupSidebar';
import NotificationsProChannelsAside from './components/NotificationsProChannelsAside';
import ChromeChannelPanel from './components/ChromeChannelPanel';
import WebhookChannelPanel from './components/WebhookChannelPanel';
import PushoverChannelPanel from './components/PushoverChannelPanel';
import SlackChannelPanel from './components/SlackChannelPanel';

const DEFAULT_SLUG = 'admin_email';
const REMOVED_CORE_SLUGS = new Set( [ 'teams', 'twilio' ] );

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

	const onSaved = () => query.refetch();

	if ( query.isLoading ) {
		return <p className="ps-v2-notify-loading">Loading notifications…</p>;
	}

	if ( query.isError ) {
		return <p className="ps-v2-notify-loading">Could not load notification settings.</p>;
	}

	if ( REMOVED_CORE_SLUGS.has( activeSlug ) && ! channels.some( ( c ) => c.slug === activeSlug ) ) {
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
			default:
				return null;
		}
	};

	const showProChannelPromos = ! isPro;

	return (
		<div className="ps-v2-notify-page" data-figma-node="672:7917">
			<div className="ps-v2-notify-layout">
				<aside className="ps-v2-notify-layout__nav">
					<NotificationsChannelNav channels={ channels } activeSlug={ activeSlug } />
				</aside>
				<div className="ps-v2-notify-layout__content">
					<NotificationsChannelPageHeader slug={ activeSlug } />
					<div className="ps-v2-notify-content-row">
						<div className="ps-v2-notify-layout__main">{ renderPanel() }</div>
						<aside className="ps-v2-notify-layout__aside">
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
