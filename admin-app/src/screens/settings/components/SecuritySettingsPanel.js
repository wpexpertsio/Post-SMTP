import { useEffect, useState } from '@wordpress/element';
import iconForceFrom from '../../../assets/settings/icon-security-force-from.svg';
import iconValidation from '../../../assets/settings/icon-security-validation.svg';
import iconDelivery from '../../../assets/settings/icon-security-delivery.svg';
import iconStealth from '../../../assets/settings/icon-security-stealth.svg';
import iconReturnPath from '../../../assets/settings/icon-security-return-path.svg';
import FormNotice from '../../connections/components/FormNotice';
import useSettingsSecurity from '../hooks/useSettingsSecurity';
import SettingsSectionCard from './SettingsSectionCard';
import SettingsSectionHeader from './SettingsSectionHeader';
import SettingsSelectField from './SettingsSelectField';
import SettingsToggleRow from './SettingsToggleRow';

const SectionIcon = ( { src } ) => (
	<span className="ps-v2-settings-section-head__icon-box ps-v2-settings-section-head__icon-box--lg ps-v2-settings-section-head__icon-box--white">
		<img src={ src } alt="" width="24" height="24" />
	</span>
);

const MAIL_DELIVERY_HELP_LINES = [
	<>
		<strong>Log only</strong> — save to the Email Log, do not send.
	</>,
	<>
		<strong>Log email &amp; send</strong> — normal sending plus logging.
	</>,
	<>
		<strong>Log failed emails only</strong> — email still sends; only failures are logged.
	</>,
	<>
		<strong>No Action</strong> — do nothing, outbound email is blocked.
	</>,
];

const MAIL_DELIVERY_HELP = (
	<div className="ps-v2-settings-security-help__lines">
		{ MAIL_DELIVERY_HELP_LINES.map( ( line, index ) => (
			<div key={ index } className="ps-v2-settings-security-help__row">
				{ line }
			</div>
		) ) }
	</div>
);

const SecuritySettingsPanel = () => {
	const { query, saveMutation } = useSettingsSecurity();
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

	const deliveryChoices = query.data?.delivery_choices || [];

	return (
		<div className="ps-v2-settings-security" data-figma-node="672:7327">
			{ notice && (
				<FormNotice
					type={ notice.type }
					message={ notice.message }
					onDismiss={ () => setNotice( null ) }
				/>
			) }

			<div className="ps-v2-settings-security__block" data-figma-node="672:7328">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconForceFrom } /> }
					title="Force From"
					description="Stop other plugins from changing your From address."
					figmaNode="672:7332"
				/>
				<SettingsSectionCard figmaNode="672:7336" className="ps-v2-settings-section--flat">
					<SettingsToggleRow
						id="force-from-email"
						label="Force form email"
						description="Always use force email from general."
						checked={ form.force_from_email }
						onChange={ ( v ) => patch( 'force_from_email', v ) }
					/>
					<SettingsToggleRow
						id="force-from-name"
						label="Force form name"
						description="Always use force name from general."
						checked={ form.force_from_name }
						onChange={ ( v ) => patch( 'force_from_name', v ) }
					/>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-security__block" data-figma-node="672:7349">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconValidation } /> }
					title="Email validation"
					description="Turn off only if another plugin needs it."
					figmaNode="672:7355"
				/>
				<SettingsSectionCard figmaNode="672:7359" className="ps-v2-settings-section--flat">
					<SettingsToggleRow
						id="disable-email-validation"
						label="Disable email validation"
						description="Allows unusual email addresses."
						checked={ form.disable_email_validation }
						onChange={ ( v ) => patch( 'disable_email_validation', v ) }
					/>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-security__block" data-figma-node="672:7366">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconDelivery } /> }
					title="Mail delivery"
					description="Choose what happens when your site sends email."
					figmaNode="672:7373"
				/>
				<SettingsSectionCard figmaNode="672:7377" className="ps-v2-settings-section--flat">
					<SettingsSelectField
						label="Mail delivery"
						hint="Log email & send is the normal choice for most sites."
						value={ form.mail_delivery }
						options={ deliveryChoices }
						onChange={ ( v ) => patch( 'mail_delivery', v ) }
					/>
					<div className="ps-v2-settings-security-help" data-figma-node="672:7383">
						{ MAIL_DELIVERY_HELP }
					</div>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-security__block" data-figma-node="672:7385">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconStealth } /> }
					title="Stealth Mode"
					description="Hide Post SMTP from email headers."
					figmaNode="672:7392"
				/>
				<SettingsSectionCard figmaNode="672:7396" className="ps-v2-settings-section--flat">
					<SettingsToggleRow
						id="stealth-mode"
						label="Stealth Mode"
						description="Remove Post SMTP name from your emails."
						checked={ form.stealth_mode }
						onChange={ ( v ) => patch( 'stealth_mode', v ) }
					/>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-security__block" data-figma-node="672:7403">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconReturnPath } /> }
					title="Return-Path"
					description="Where bounced emails go."
					figmaNode="672:7407"
				/>
				<SettingsSectionCard figmaNode="672:7411" className="ps-v2-settings-section--flat">
					<div className="ps-v2-settings-field">
						<div className="ps-v2-settings-field__head">
							<span className="ps-v2-settings-field__label">Envelope sender (Return-Path)</span>
							<p className="ps-v2-settings-field__hint">Leave blank to use your From Email.</p>
						</div>
						<input
							type="email"
							className="ps-v2-settings-input"
							value={ form.envelope_sender }
							onChange={ ( e ) => patch( 'envelope_sender', e.target.value ) }
							placeholder="Placeholder"
						/>
					</div>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-save-wrap" data-figma-node="672:7417">
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

export default SecuritySettingsPanel;
