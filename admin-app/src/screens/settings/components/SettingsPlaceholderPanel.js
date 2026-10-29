import { PLACEHOLDER_COPY, SETTINGS_NAV } from '../settingsNav';
import SettingsSectionCard from './SettingsSectionCard';
import SettingsSectionHeader from './SettingsSectionHeader';
import { SETTINGS_NAV_ICONS } from '../settingsNavAssets';

const SettingsPlaceholderPanel = ( { slug } ) => {
	const nav = SETTINGS_NAV.find( ( item ) => item.slug === slug );
	const copy = PLACEHOLDER_COPY[ slug ] || {
		title: nav?.label || 'Settings',
		description: 'This section is coming soon in the revamp.',
	};
	const icon = SETTINGS_NAV_ICONS[ slug ];

	return (
		<div className="ps-v2-settings-placeholder">
			<SettingsSectionCard>
				<SettingsSectionHeader
					icon={
						icon ? (
							<span className="ps-v2-settings-section-head__icon-box">
								<img src={ icon } alt="" width="20" height="20" />
							</span>
						) : null
					}
					title={ copy.title }
					description={ copy.description }
				/>
				<p className="ps-v2-settings-placeholder__note">
					We&apos;re building this tab to match the Figma settings designs. General settings are
					available now.
				</p>
			</SettingsSectionCard>
		</div>
	);
};

export default SettingsPlaceholderPanel;
