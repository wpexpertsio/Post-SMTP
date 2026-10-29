import { Navigate, useParams } from 'react-router-dom';
import SettingsNav from './components/SettingsNav';
import EmailLogsSettingsPanel from './components/EmailLogsSettingsPanel';
import EmailQueueSettingsPanel from './components/EmailQueueSettingsPanel';
import GeneralSettingsPanel from './components/GeneralSettingsPanel';
import AdvancedSettingsPanel from './components/AdvancedSettingsPanel';
import EmailReportingSettingsPanel from './components/EmailReportingSettingsPanel';
import SecuritySettingsPanel from './components/SecuritySettingsPanel';
import SettingsPlaceholderPanel from './components/SettingsPlaceholderPanel';
import { DEFAULT_SETTINGS_SLUG, SETTINGS_NAV } from './settingsNav';

const SettingsScreen = () => {
	const { settingsSlug = DEFAULT_SETTINGS_SLUG } = useParams();
	const valid = SETTINGS_NAV.some( ( item ) => item.slug === settingsSlug );

	if ( ! valid ) {
		return <Navigate to={ `/settings/${ DEFAULT_SETTINGS_SLUG }` } replace />;
	}

	const renderPanel = () => {
		if ( settingsSlug === 'general' ) {
			return <GeneralSettingsPanel />;
		}
		if ( settingsSlug === 'email-logs' ) {
			return <EmailLogsSettingsPanel />;
		}
		if ( settingsSlug === 'email-queue' ) {
			return <EmailQueueSettingsPanel />;
		}
		if ( settingsSlug === 'security' ) {
			return <SecuritySettingsPanel />;
		}
		if ( settingsSlug === 'advanced' ) {
			return <AdvancedSettingsPanel />;
		}
		if ( settingsSlug === 'email-reporting' ) {
			return <EmailReportingSettingsPanel />;
		}
		return <SettingsPlaceholderPanel slug={ settingsSlug } />;
	};

	return (
		<div className="ps-v2-settings-page" data-figma-node="672:7182">
			<div className="ps-v2-settings-layout">
				<aside className="ps-v2-settings-layout__nav">
					<SettingsNav activeSlug={ settingsSlug } />
				</aside>
				<div className="ps-v2-settings-layout__content">{ renderPanel() }</div>
			</div>
		</div>
	);
};

export default SettingsScreen;
