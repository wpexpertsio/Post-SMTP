import { useEffect, useState } from '@wordpress/element';
import iconOpenTracking from '../../../assets/settings/icon-reporting-open-tracking.svg';
import iconWeekly from '../../../assets/settings/icon-reporting-weekly.svg';
import iconUpgradeArrow from '../../../assets/help-docs/icon-upgrade-arrow.svg';
import { getAdminUrls } from '../../../config/admin';
import { proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';
import FormNotice from '../../connections/components/FormNotice';
import useSettingsEmailReporting from '../hooks/useSettingsEmailReporting';
import SettingsSectionCard from './SettingsSectionCard';
import SettingsSectionHeader from './SettingsSectionHeader';
import SettingsSelectField from './SettingsSelectField';
import SettingsToggleRow from './SettingsToggleRow';

const SectionIcon = ( { src } ) => (
	<span className="ps-v2-settings-section-head__icon-box ps-v2-settings-section-head__icon-box--lg ps-v2-settings-section-head__icon-box--white">
		<img src={ src } alt="" width="24" height="24" />
	</span>
);

const ProTag = () => (
	<span className="ps-v2-mailer-pro-tag">
		<img src={ proCrownIcon } alt="" width="11" height="9" />
		PRO
	</span>
);

const OpenTrackingSectionHeader = ( { panel } ) => (
	<header className="ps-v2-settings-section-head" data-figma-node="672:7481">
		<span className="ps-v2-settings-section-head__icon" aria-hidden="true">
			<SectionIcon src={ iconOpenTracking } />
		</span>
		<div className="ps-v2-settings-section-head__copy">
			<div className="ps-v2-settings-title-with-pro">
				<h2 className="ps-v2-settings-section-head__title">{ panel.title }</h2>
				{ panel.show_pro_tag && <ProTag /> }
			</div>
			{ panel.description && (
				<p className="ps-v2-settings-section-head__desc">{ panel.description }</p>
			) }
		</div>
	</header>
);

const EmailReportingSettingsPanel = () => {
	const urls = getAdminUrls();
	const { query, saveMutation } = useSettingsEmailReporting();
	const [ form, setForm ] = useState( null );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		if ( query.data?.values ) {
			setForm( ( prev ) => {
				if ( prev ) {
					return prev;
				}
				const toggle = query.data?.open_tracking?.toggle;
				return {
					...query.data.values,
					track_email_opens: toggle?.checked ?? false,
				};
			} );
		}
	}, [ query.data ] );

	const patch = ( key, value ) => {
		setForm( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	const syncFromSaveResult = ( result ) => {
		if ( result?.values ) {
			const toggle = result?.open_tracking?.toggle;
			setForm( {
				...result.values,
				track_email_opens: toggle?.checked ?? false,
			} );
		}
	};

	const handleSave = async () => {
		setNotice( null );
		const openTracking = query.data?.open_tracking || {};
		const body = {
			enable_weekly_report: form.enable_weekly_report,
			reporting_interval: form.reporting_interval,
		};
		if ( ! openTracking.locked ) {
			body.track_email_opens = form.track_email_opens;
		}

		try {
			const result = await saveMutation.mutateAsync( body );
			syncFromSaveResult( result );
			setNotice( { type: 'success', message: result?.message || 'Settings saved.' } );
		} catch ( err ) {
			setNotice( {
				type: 'error',
				message: err?.message || 'Could not save settings.',
			} );
		}
	};

	if ( query.isLoading || ! form ) {
		return <p className="ps-v2-settings-loading">Loading settings…</p>;
	}

	if ( query.isError ) {
		return <p className="ps-v2-settings-loading">Could not load settings.</p>;
	}

	const frequencyChoices = query.data?.frequency_choices || [];
	const openTracking = query.data?.open_tracking || {};
	const toggleMeta = openTracking.toggle || {};
	const panelConfig = query.data?.panel_config || {};
	const upgradeUrl = panelConfig.upgrade_url || urls.pro;
	const upgradeLabel = panelConfig.upgrade_label || 'Upgrade to Pro';
	const trackingLocked = Boolean( openTracking.locked );

	return (
		<div className="ps-v2-settings-email-reporting" data-figma-node="672:7450">
			{ notice && (
				<FormNotice
					type={ notice.type }
					message={ notice.message }
					onDismiss={ () => setNotice( null ) }
				/>
			) }

			<div className="ps-v2-settings-email-reporting__block" data-figma-node="672:7451">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconWeekly } /> }
					title="Weekly Summary Report"
					description="Get a weekly email about sends and failures."
					figmaNode="672:7458"
				/>
				<SettingsSectionCard figmaNode="672:7462" className="ps-v2-settings-section--flat">
					<SettingsToggleRow
						id="enable-weekly-report"
						label="Enable Weekly Summary Report"
						description="Sent to your WordPress admin email."
						checked={ form.enable_weekly_report }
						onChange={ ( v ) => patch( 'enable_weekly_report', v ) }
					/>
					<SettingsSelectField
						label="Report frequency"
						hint="How often to send it."
						value={ form.reporting_interval }
						options={ frequencyChoices }
						onChange={ ( v ) => patch( 'reporting_interval', v ) }
						disabled={ ! form.enable_weekly_report }
					/>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-email-reporting__block" data-figma-node="672:7475">
				<OpenTrackingSectionHeader panel={ openTracking } />
				<SettingsSectionCard figmaNode="672:7485" className="ps-v2-settings-section--flat">
					<SettingsToggleRow
						id="track-email-opens"
						label={ toggleMeta.label || 'Track email opens' }
						description={ toggleMeta.hint || 'Add a small hidden image to your emails.' }
						checked={ trackingLocked ? false : form.track_email_opens }
						onChange={ ( v ) => patch( 'track_email_opens', v ) }
						disabled={ trackingLocked || toggleMeta.disabled }
					/>
				</SettingsSectionCard>
			</div>

			{ openTracking.show_upgrade_cta && (
				<div className="ps-v2-settings-email-reporting__upgrade" data-figma-node="1007:24774">
					<a
						href={ upgradeUrl }
						className="ps-v2-reports-cta ps-v2-settings-email-reporting__upgrade-btn"
						target="_blank"
						rel="noreferrer"
					>
						{ upgradeLabel }
						<img src={ iconUpgradeArrow } alt="" width="14" height="10" />
					</a>
				</div>
			) }

			<div className="ps-v2-settings-save-wrap" data-figma-node="672:7493">
				<button
					type="button"
					className="ps-v2-settings-save"
					onClick={ handleSave }
					disabled={ saveMutation.isPending }
				>
					{ saveMutation.isPending ? 'Saving…' : 'Save Changes' }
				</button>
			</div>
		</div>
	);
};

export default EmailReportingSettingsPanel;
