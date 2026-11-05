import { useEffect, useState } from '@wordpress/element';
import iconEmailQueue from '../../../assets/settings/nav-email-queue.svg';
import iconEmailQueueHeader from '../../../assets/settings/icon-email-queue-header.svg';
import previewAdvanced from '../../../assets/settings/email-queue/preview-advanced.svg';
import previewBatch from '../../../assets/settings/email-queue/preview-batch.svg';
import previewQuota from '../../../assets/settings/email-queue/preview-quota.svg';
import previewRetry from '../../../assets/settings/email-queue/preview-retry.svg';
import { getAdminConfig, getAdminUrls } from '../../../config/admin';
import { proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';
import FormNotice from '../../connections/components/FormNotice';
import useSettingsEmailQueue from '../hooks/useSettingsEmailQueue';
import ImageLightbox from './ImageLightbox';
import ProFeaturePreviewCard from './ProFeaturePreviewCard';
import SettingsSectionCard from './SettingsSectionCard';
import SettingsSectionHeader from './SettingsSectionHeader';
import SettingsSelectField from './SettingsSelectField';
import SettingsToggleRow from './SettingsToggleRow';

const PREVIEW_META = [
	{
		id: 'advanced_delivery',
		imageSrc: previewAdvanced,
		title: 'Advanced Delivery',
		description:
			'Queue outgoing emails and send them automatically in the background to support higher email volumes.',
	},
	{
		id: 'batch_settings',
		imageSrc: previewBatch,
		title: 'Batch Settings',
		description:
			'Control how many emails are sent in each batch and the waiting time between batches to manage server load.',
	},
	{
		id: 'quota_settings',
		imageSrc: previewQuota,
		title: 'Quota Settings',
		description:
			'Set email sending limits for an hour, day, week, or month. Additional queued emails wait until the next available quota period.',
	},
	{
		id: 'auto_retry',
		imageSrc: previewRetry,
		title: 'Auto Retry',
		description:
			'Automatically retry failed email sends using a configurable retry limit to improve delivery reliability.',
	},
];

const SectionIcon = ( { src } ) => (
	<span className="ps-v2-settings-section-head__icon-box ps-v2-settings-section-head__icon-box--lg">
		<img src={ src } alt="" width="24" height="24" />
	</span>
);

const ProTag = () => (
	<span className="ps-v2-mailer-pro-tag">
		<img src={ proCrownIcon } alt="" width="11" height="9" />
		PRO
	</span>
);

const SettingsNumberField = ( { label, hint, value, onChange, min = 1, disabled = false } ) => (
	<div className="ps-v2-settings-field">
		<div className="ps-v2-settings-field__head">
			<span className="ps-v2-settings-field__label">{ label }</span>
			{ hint && <p className="ps-v2-settings-field__hint">{ hint }</p> }
		</div>
		<input
			type="number"
			className="ps-v2-settings-input"
			value={ value }
			min={ min }
			disabled={ disabled }
			onChange={ ( e ) => onChange( parseInt( e.target.value, 10 ) || min ) }
		/>
	</div>
);

const EmailQueueFreePreviews = ( { upgradeUrl } ) => (
	<div className="ps-v2-settings-email-queue ps-v2-settings-queue-previews" data-figma-node="672:7812">
		<header className="ps-v2-settings-queue-previews__head">
			<span
				className="ps-v2-settings-section-head__icon-box ps-v2-settings-section-head__icon-box--lg ps-v2-settings-section-head__icon-box--white"
				aria-hidden="true"
			>
				<img src={ iconEmailQueueHeader } alt="" width="24" height="24" />
			</span>
			<div className="ps-v2-settings-queue-previews__title-row">
				<h2 className="ps-v2-settings-section-head__title">Email Queue</h2>
				<ProTag />
			</div>
		</header>

		<div className="ps-v2-settings-queue-previews__list">
			{ PREVIEW_META.map( ( item ) => (
				<ProFeaturePreviewCard
					key={ item.id }
					title={ item.title }
					description={ item.description }
					imageSrc={ item.imageSrc }
					upgradeUrl={ upgradeUrl }
				/>
			) ) }
		</div>
	</div>
);

const EmailQueueSettingsPanel = () => {
	const config = getAdminConfig();
	const urls = getAdminUrls();
	const { query, saveMutation } = useSettingsEmailQueue();
	const [ form, setForm ] = useState( null );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		if ( query.data?.values ) {
			setForm( ( prev ) => ( prev ? prev : { ...query.data.values } ) );
		}
	}, [ query.data ] );

	const panelConfig = query.data?.panel_config || {};
	const upgradeUrl = panelConfig.upgrade_url || urls.pro || 'https://postmansmtp.com/pricing/';
	const isPro = query.data?.is_pro ?? config.isPro;
	const panelLocked = query.data?.panel_locked ?? ! isPro;

	if ( query.isLoading ) {
		return <p className="ps-v2-settings-loading">Loading settings…</p>;
	}

	if ( query.isError ) {
		return <p className="ps-v2-settings-loading">Could not load settings.</p>;
	}

	if ( panelLocked ) {
		return <EmailQueueFreePreviews upgradeUrl={ upgradeUrl } />;
	}

	if ( ! form ) {
		return <p className="ps-v2-settings-loading">Loading settings…</p>;
	}

	const quotaChoices = query.data?.quota_duration_choices || [];
	const retryChoices = query.data?.retry_attempt_choices || [];

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

	return (
		<div className="ps-v2-settings-email-queue" data-figma-node="672:7812">
			{ notice && (
				<FormNotice
					type={ notice.type }
					message={ notice.message }
					onDismiss={ () => setNotice( null ) }
				/>
			) }

			<div className="ps-v2-settings-email-queue__block" data-figma-node="1065:43424">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconEmailQueue } /> }
					title="Advanced Delivery"
					description="Send in the background with quotas and retries."
					figmaNode="1065:43428"
				/>
				<SettingsSectionCard figmaNode="1065:43433" className="ps-v2-settings-section--flat">
					<SettingsToggleRow
						id="enable-advance-delivery"
						label="Enable Advanced Delivery"
						description="Queues email and sends it in the background."
						checked={ form.enable_advance_delivery }
						onChange={ ( v ) => patch( 'enable_advance_delivery', v ) }
					/>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-email-queue__block" data-figma-node="672:7828">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconEmailQueue } /> }
					title="Batch settings"
					description="How many emails go out at once."
					figmaNode="672:7835"
				/>
				<SettingsSectionCard figmaNode="672:7839" className="ps-v2-settings-section--flat">
					<SettingsNumberField
						label="Emails per batch"
						hint="Emails sent in one run."
						value={ form.emails_per_batch }
						min={ 1 }
						onChange={ ( v ) => patch( 'emails_per_batch', v ) }
					/>
					<SettingsNumberField
						label="Duration between batches (seconds)"
						hint="Wait time between batches."
						value={ form.batch_duration }
						min={ 1 }
						onChange={ ( v ) => patch( 'batch_duration', v ) }
					/>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-email-queue__block" data-figma-node="672:7851">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconEmailQueue } /> }
					title="Quota settings"
					description="Limit how many emails this site sends."
					figmaNode="672:7855"
				/>
				<SettingsSectionCard figmaNode="672:7859" className="ps-v2-settings-section--flat">
					<SettingsToggleRow
						id="enable-quota"
						label="Enable quota"
						description="Stop sending at the limit."
						checked={ form.enable_quota }
						onChange={ ( v ) => patch( 'enable_quota', v ) }
					/>
					<SettingsToggleRow
						id="advanced-quota"
						label="Advanced quota settings"
						description="Show the email quota meter on the Reports page."
						checked={ form.advanced_quota_settings }
						onChange={ ( v ) => patch( 'advanced_quota_settings', v ) }
					/>
					<SettingsNumberField
						label="Email quota value"
						hint="Max emails per period."
						value={ form.quota_value }
						min={ 1 }
						disabled={ ! form.enable_quota }
						onChange={ ( v ) => patch( 'quota_value', v ) }
					/>
					<SettingsSelectField
						label="Email quota duration"
						hint="Hour, day, week, or month."
						value={ form.quota_duration }
						options={ quotaChoices }
						disabled={ ! form.enable_quota }
						onChange={ ( v ) => patch( 'quota_duration', v ) }
					/>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-email-queue__block">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconEmailQueue } /> }
					title="Auto Retry"
					description="Try failed emails again automatically."
				/>
				<SettingsSectionCard className="ps-v2-settings-section--flat">
					<SettingsToggleRow
						id="auto-retry-enabled-queue"
						label="Enable Auto Retry"
						description="Automatically retry failed email sends."
						checked={ form.auto_retry_enabled }
						onChange={ ( v ) => patch( 'auto_retry_enabled', v ) }
					/>
					<SettingsSelectField
						label="Times to Retry"
						hint="Maximum automatic retries after the initial failure."
						value={ form.auto_retry_max_attempts }
						options={ retryChoices }
						disabled={ ! form.auto_retry_enabled }
						onChange={ ( v ) => patch( 'auto_retry_max_attempts', parseInt( v, 10 ) ) }
					/>
				</SettingsSectionCard>
			</div>

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

export default EmailQueueSettingsPanel;
export { ImageLightbox };
