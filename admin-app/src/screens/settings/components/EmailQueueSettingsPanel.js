import { useEffect, useState } from '@wordpress/element';
import iconEmailQueue from '../../../assets/settings/nav-email-queue.svg';
import iconEmailQueueHeader from '../../../assets/settings/icon-email-queue-header.svg';
import checkIcon from '../../../assets/dashboard/pro-banner/check.svg';
import iconUpgradeArrow from '../../../assets/help-docs/icon-upgrade-arrow.svg';
import { getAdminConfig, getAdminUrls } from '../../../config/admin';
import { proCrownIcon } from '../../dashboard/components/v2/gmailBarAssets';
import FormNotice from '../../connections/components/FormNotice';
import useSettingsEmailQueue from '../hooks/useSettingsEmailQueue';
import SettingsSectionCard from './SettingsSectionCard';
import SettingsSectionHeader from './SettingsSectionHeader';
import SettingsSelectField from './SettingsSelectField';
import SettingsToggleRow from './SettingsToggleRow';

const PRO_QUEUE_FEATURES = [
	'Advanced Delivery',
	'Batch settings',
	'Quota settings',
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

const SettingsNumberField = ( { label, hint, value, onChange, min = 0, disabled = false } ) => (
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
			onChange={ ( e ) => onChange( parseInt( e.target.value, 10 ) || 0 ) }
		/>
	</div>
);

const EmailQueueFreeUpsell = ( { urls } ) => (
	<div className="ps-v2-settings-email-queue ps-v2-settings-queue-upsell" data-figma-node="672:7812">
		<div className="ps-v2-settings-queue-upsell__main" data-figma-node="672:7813">
			<header className="ps-v2-settings-queue-upsell__head" data-figma-node="672:7814">
				<span
					className="ps-v2-settings-section-head__icon-box ps-v2-settings-section-head__icon-box--lg ps-v2-settings-section-head__icon-box--white"
					aria-hidden="true"
				>
					<img src={ iconEmailQueueHeader } alt="" width="24" height="24" />
				</span>
				<div className="ps-v2-settings-queue-upsell__title-row" data-figma-node="672:7818">
					<h2 className="ps-v2-settings-section-head__title">Email Queue</h2>
					<ProTag />
				</div>
			</header>

			<SettingsSectionCard
				className="ps-v2-settings-section--flat ps-v2-settings-queue-upsell__card"
				figmaNode="672:7821"
			>
				<p className="ps-v2-settings-queue-upsell__lead" data-figma-node="1065:43465">
					Unlock these awesome logging features:
				</p>
				<ul className="ps-v2-settings-queue-upsell__features" data-figma-node="1065:43453">
					{ PRO_QUEUE_FEATURES.map( ( label ) => (
						<li key={ label }>
							<img src={ checkIcon } alt="" width="14" height="14" aria-hidden="true" />
							<span>{ label }</span>
						</li>
					) ) }
				</ul>
			</SettingsSectionCard>

			<div className="ps-v2-settings-queue-upsell__previews" data-figma-node="1065:43483" aria-hidden="true">
				<div className="ps-v2-settings-queue-upsell__preview" data-figma-node="1065:43468" />
				<div className="ps-v2-settings-queue-upsell__preview" data-figma-node="1065:43484" />
				<div className="ps-v2-settings-queue-upsell__preview" data-figma-node="1065:43486" />
			</div>
		</div>

		<div className="ps-v2-settings-queue-upsell__cta-wrap" data-figma-node="672:7886">
			<a href={ urls.pro } className="ps-v2-reports-cta ps-v2-settings-queue-upsell__cta" target="_blank" rel="noreferrer">
				Upgrade to Pro
				<img src={ iconUpgradeArrow } alt="" width="14" height="10" />
			</a>
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

	const isPro = query.data?.is_pro ?? config.isPro;

	if ( query.isLoading ) {
		return <p className="ps-v2-settings-loading">Loading settings…</p>;
	}

	if ( query.isError ) {
		return <p className="ps-v2-settings-loading">Could not load settings.</p>;
	}

	if ( ! isPro ) {
		return <EmailQueueFreeUpsell urls={ urls } />;
	}

	if ( ! form ) {
		return <p className="ps-v2-settings-loading">Loading settings…</p>;
	}

	const quotaChoices = query.data?.quota_duration_choices || [];

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
					title={
						<span className="ps-v2-settings-title-with-pro">
							Advanced Delivery
							<ProTag />
						</span>
					}
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
					title={
						<span className="ps-v2-settings-title-with-pro">
							Batch settings
							<ProTag />
						</span>
					}
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
						min={ 0 }
						onChange={ ( v ) => patch( 'batch_duration', v ) }
					/>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-email-queue__block" data-figma-node="672:7851">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconEmailQueue } /> }
					title={
						<span className="ps-v2-settings-title-with-pro">
							Quota settings
							<ProTag />
						</span>
					}
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
						hint="Day, week or month."
						value={ form.quota_duration }
						options={ quotaChoices }
						disabled={ ! form.enable_quota }
						onChange={ ( v ) => patch( 'quota_duration', v ) }
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
