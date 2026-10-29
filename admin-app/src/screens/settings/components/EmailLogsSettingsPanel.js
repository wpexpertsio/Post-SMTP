import { useEffect, useState } from '@wordpress/element';
import { Link } from 'react-router-dom';
import iconExternalLink from '../../../assets/dashboard/connections/helper/icon-external-link.svg';
import iconEmailLogs from '../../../assets/settings/nav-email-logs.svg';
import checkIcon from '../../../assets/dashboard/pro-banner/check.svg';
import iconUpgradeArrow from '../../../assets/help-docs/icon-upgrade-arrow.svg';
import { getAdminConfig, getAdminUrls } from '../../../config/admin';
import { proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';
import FormNotice from '../../connections/components/FormNotice';
import useSettingsEmailLogs from '../hooks/useSettingsEmailLogs';
import SettingsSectionCard from './SettingsSectionCard';
import SettingsSectionHeader from './SettingsSectionHeader';
import SettingsSelectField from './SettingsSelectField';
import SettingsToggleRow from './SettingsToggleRow';

const PRO_LOG_FEATURES = [
	'Save attachments',
	'Advance log filter',
	'Delete logs after days',
	'Export logs',
];

const SectionIcon = ( { src } ) => (
	<span className="ps-v2-settings-section-head__icon-box ps-v2-settings-section-head__icon-box--lg">
		<img src={ src } alt="" width="24" height="24" />
	</span>
);

const EmailLogsSettingsPanel = () => {
	const config = getAdminConfig();
	const urls = getAdminUrls();
	const { query, saveMutation } = useSettingsEmailLogs();
	const [ form, setForm ] = useState( null );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		if ( query.data?.values ) {
			setForm( ( prev ) => ( prev ? prev : { ...query.data.values } ) );
		}
	}, [ query.data ] );

	const patch = ( key, value ) => {
		setForm( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	const handleSave = async () => {
		setNotice( null );
		try {
			const result = await saveMutation.mutateAsync( form );
			if ( result?.values ) {
				setForm( { ...result.values } );
			}
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

	const maxChoices = query.data?.max_entries_choices || [];
	const transcriptChoices = query.data?.transcript_choices || [];
	const retryChoices = query.data?.retry_attempt_choices || [];
	const isPro = query.data?.is_pro ?? config.isPro;
	const loggingAllowed = query.data?.logging_allowed !== false;
	const autoRetryLocked = ! isPro;

	return (
		<div className="ps-v2-settings-email-logs" data-figma-node="672:7641">
			{ notice && (
				<FormNotice
					type={ notice.type }
					message={ notice.message }
					onDismiss={ () => setNotice( null ) }
				/>
			) }

			<div className="ps-v2-settings-email-logs__block" data-figma-node="672:7642">
				<div className="ps-v2-settings-page-head" data-figma-node="672:7643">
					<SettingsSectionHeader
						icon={ <SectionIcon src={ iconEmailLogs } /> }
						title="Email Log"
						description="Save a copy of every email your site sends."
						figmaNode="672:7647"
					/>
					<Link to="/email-logs" className="ps-v2-settings-view-log" data-figma-node="672:7651">
						<span>View email log</span>
						<img src={ iconExternalLink } alt="" width="13" height="13" />
					</Link>
				</div>

				<SettingsSectionCard figmaNode="672:7652" className="ps-v2-settings-section--flat">
					<SettingsToggleRow
						id="logging-enabled"
						label="Enable email logging"
						checked={ form.logging_enabled }
						disabled={ ! loggingAllowed }
						onChange={ ( v ) => patch( 'logging_enabled', v ) }
					/>
					<SettingsToggleRow
						id="save-message-body"
						label="Save message body (needed for resend and preview)"
						checked={ form.save_message_body }
						onChange={ ( v ) => patch( 'save_message_body', v ) }
					/>
					<SettingsSelectField
						label="Maximum log entries"
						hint="Older entries are removed automatically."
						value={ form.max_log_entries }
						options={ maxChoices }
						onChange={ ( v ) => patch( 'max_log_entries', parseInt( v, 10 ) ) }
					/>
					<SettingsSelectField
						label="Maximum Transcript Size"
						hint="How much detail to keep per email."
						value={ form.transcript_size_kb }
						options={ transcriptChoices }
						onChange={ ( v ) => patch( 'transcript_size_kb', parseInt( v, 10 ) ) }
					/>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-email-logs__block" data-figma-node="672:7692">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconEmailLogs } /> }
					title="Auto Retry"
					description="Try failed emails again automatically."
					figmaNode="672:7696"
				/>
				<SettingsSectionCard figmaNode="672:7700" className="ps-v2-settings-section--flat">
					<SettingsToggleRow
						id="auto-retry-enabled"
						label="Enable auto-retry for failed emails"
						checked={ form.auto_retry_enabled }
						disabled={ autoRetryLocked }
						onChange={ ( v ) => patch( 'auto_retry_enabled', v ) }
					/>
					<SettingsSelectField
						label="Maximum Retry Attempts"
						hint="Attempts before the email is marked Failed. Default is 5."
						value={ form.auto_retry_max_attempts }
						options={ retryChoices }
						disabled={ autoRetryLocked || ! form.auto_retry_enabled }
						onChange={ ( v ) => patch( 'auto_retry_max_attempts', parseInt( v, 10 ) ) }
					/>
				</SettingsSectionCard>
			</div>

			{ ! isPro && (
				<section className="ps-v2-settings-log-pro" data-figma-node="1065:43488">
					<header className="ps-v2-settings-log-pro__head" data-figma-node="1065:43489">
						<span className="ps-v2-settings-section-head__icon-box ps-v2-settings-section-head__icon-box--lg">
							<img src={ iconEmailLogs } alt="" width="24" height="24" />
						</span>
						<div className="ps-v2-settings-log-pro__title-row">
							<h2 className="ps-v2-settings-section-head__title">More Features</h2>
							<span className="ps-v2-mailer-pro-tag">
								<img src={ proCrownIcon } alt="" width="11" height="9" />
								PRO
							</span>
						</div>
					</header>

					<SettingsSectionCard className="ps-v2-settings-section--flat ps-v2-settings-log-pro__card">
						<p className="ps-v2-settings-log-pro__lead">Unlock these awesome logging features:</p>
						<ul className="ps-v2-settings-log-pro__features" data-figma-node="1065:43504">
							{ PRO_LOG_FEATURES.map( ( label ) => (
								<li key={ label }>
									<img src={ checkIcon } alt="" width="14" height="14" aria-hidden="true" />
									<span>{ label }</span>
								</li>
							) ) }
						</ul>
					</SettingsSectionCard>

					<div className="ps-v2-settings-log-pro__previews" aria-hidden="true">
						<div className="ps-v2-settings-log-pro__preview" />
						<div className="ps-v2-settings-log-pro__preview" />
						<div className="ps-v2-settings-log-pro__preview" />
					</div>

					<div className="ps-v2-settings-log-pro__cta-wrap" data-figma-node="672:7779">
						<a
							href={ urls.pro }
							className="ps-v2-reports-cta"
							target="_blank"
							rel="noreferrer"
						>
							Upgrade to Pro
							<img src={ iconUpgradeArrow } alt="" width="14" height="10" />
						</a>
					</div>
				</section>
			) }

			<div className="ps-v2-settings-save-wrap">
				<button
					type="button"
					className="ps-v2-settings-save"
					onClick={ handleSave }
					disabled={ saveMutation.isPending }
				>
					{ saveMutation.isPending ? 'Saving…' : 'Save changes' }
				</button>
			</div>
		</div>
	);
};

export default EmailLogsSettingsPanel;
