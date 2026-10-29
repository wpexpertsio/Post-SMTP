import { useEffect, useState } from '@wordpress/element';
import { Link } from 'react-router-dom';
import iconChevronDown from '../../../assets/tools/icon-chevron-down.svg';
import iconGeneral from '../../../assets/settings/nav-general.svg';
import FormNotice from '../../connections/components/FormNotice';
import useSettingsGeneral from '../hooks/useSettingsGeneral';
import SettingsSectionCard from './SettingsSectionCard';
import SettingsSectionHeader from './SettingsSectionHeader';

const SectionIcon = ( { src } ) => (
	<span className="ps-v2-settings-section-head__icon-box">
		<img src={ src } alt="" width="20" height="20" />
	</span>
);

const SettingsField = ( { label, hint, children } ) => (
	<div className="ps-v2-settings-field">
		<div className="ps-v2-settings-field__head">
			<span className="ps-v2-settings-field__label">{ label }</span>
			{ hint && <p className="ps-v2-settings-field__hint">{ hint }</p> }
		</div>
		{ children }
	</div>
);

const SettingsCheckbox = ( { id, label, checked, onChange } ) => (
	<label className="ps-v2-settings-check" htmlFor={ id }>
		<input id={ id } type="checkbox" checked={ checked } onChange={ ( e ) => onChange( e.target.checked ) } />
		<span>{ label }</span>
	</label>
);

const GeneralSettingsPanel = ( { onSaved } ) => {
	const { query, saveMutation } = useSettingsGeneral();
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
			onSaved?.();
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

	const handlers = query.data?.mail_handlers || [];
	const primaryLabel = query.data?.primary_label || '';
	const handlerLabel =
		handlers.find( ( h ) => h.value === form.mail_handler )?.label || 'Post SMTP';

	return (
		<div className="ps-v2-settings-general" data-figma-node="1096:45074">
			{ notice && (
				<FormNotice
					type={ notice.type }
					message={ notice.message }
					onDismiss={ () => setNotice( null ) }
				/>
			) }

			<SettingsSectionCard figmaNode="1096:45075">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconGeneral } /> }
					title="WordPress mail handler"
					description="Who handles wp_mail on this site."
					figmaNode="1096:45076"
				/>
				<div className="ps-v2-settings-panel">
					<SettingsField
						label="Mail handler"
						hint="Mailer accounts are configured in Connections."
					>
						<div className="ps-v2-settings-select-wrap">
							<label className="ps-v2-settings-select">
								<span className="ps-v2-settings-select__face">{ handlerLabel }</span>
								<img
									className="ps-v2-settings-select__chevron"
									src={ iconChevronDown }
									alt=""
									width="10"
									height="6"
								/>
								<select
									className="ps-v2-settings-select__native"
									value={ form.mail_handler }
									onChange={ ( e ) => patch( 'mail_handler', e.target.value ) }
									aria-label="Mail handler"
								>
									{ handlers.map( ( opt ) => (
										<option key={ opt.value } value={ opt.value }>
											{ opt.label }
										</option>
									) ) }
								</select>
							</label>
						</div>
					</SettingsField>
				</div>
			</SettingsSectionCard>

			<SettingsSectionCard figmaNode="1096:45094">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconGeneral } /> }
					title="From details"
					description="Global sender identity, mapped to your Primary connection."
					figmaNode="1096:45095"
				/>
				<div className="ps-v2-settings-panel">
					<div className="ps-v2-settings-callout" data-figma-node="1096:45105">
						<p>
							{ primaryLabel ? (
								<>
									These values apply to your Primary connection{ ' ' }
									<strong>{ primaryLabel }</strong>.
								</>
							) : (
								<>These values apply to your Primary connection.</>
							) }{ ' ' }
							<Link to="/connections">Manage in Connections</Link>.
						</p>
					</div>

					<SettingsField
						label="From Name"
						hint="Your site or brand name. Used by the Primary connection."
					>
						<input
							type="text"
							className="ps-v2-settings-input"
							value={ form.from_name }
							onChange={ ( e ) => patch( 'from_name', e.target.value ) }
						/>
						<SettingsCheckbox
							id="enforce-from-name"
							label="Enforce this name on the Primary connection, even when another plugin sets a different name."
							checked={ form.enforce_from_name }
							onChange={ ( v ) => patch( 'enforce_from_name', v ) }
						/>
					</SettingsField>

					<SettingsField
						label="From Email"
						hint="Use an email on your own domain. Used by the Primary connection."
					>
						<input
							type="email"
							className="ps-v2-settings-input"
							value={ form.from_email }
							onChange={ ( e ) => patch( 'from_email', e.target.value ) }
						/>
						<SettingsCheckbox
							id="enforce-from-email"
							label="Enforce this address on the Primary connection, even when another plugin sets a different address."
							checked={ form.enforce_from_email }
							onChange={ ( v ) => patch( 'enforce_from_email', v ) }
						/>
					</SettingsField>
				</div>
			</SettingsSectionCard>

			<SettingsSectionCard figmaNode="1096:45122">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconGeneral } /> }
					title="Extra addresses"
					description="Reply-To, CC and BCC for every email."
					figmaNode="1096:45123"
				/>
				<div className="ps-v2-settings-panel">
					<SettingsField label="Reply-To name">
						<input
							type="text"
							className="ps-v2-settings-input"
							value={ form.reply_to_name }
							onChange={ ( e ) => patch( 'reply_to_name', e.target.value ) }
						/>
					</SettingsField>

					<SettingsField label="Reply-To email" hint="Where replies should go.">
						<input
							type="email"
							className="ps-v2-settings-input"
							value={ form.reply_to_email }
							onChange={ ( e ) => patch( 'reply_to_email', e.target.value ) }
						/>
					</SettingsField>

					<SettingsField label="To recipient(s)" hint="Send a copy to these addresses.">
						<input
							type="text"
							className="ps-v2-settings-input"
							value={ form.forced_to }
							onChange={ ( e ) => patch( 'forced_to', e.target.value ) }
						/>
					</SettingsField>

					<SettingsField label="Carbon copy (CC)" hint="Visible to all recipients.">
						<input
							type="text"
							className="ps-v2-settings-input"
							value={ form.forced_cc }
							onChange={ ( e ) => patch( 'forced_cc', e.target.value ) }
						/>
					</SettingsField>

					<SettingsField label="Blind carbon copy (BCC)" hint="Hidden from other recipients.">
						<input
							type="text"
							className="ps-v2-settings-input"
							value={ form.forced_bcc }
							onChange={ ( e ) => patch( 'forced_bcc', e.target.value ) }
						/>
					</SettingsField>
				</div>
			</SettingsSectionCard>

			<div className="ps-v2-settings-save-wrap" data-figma-node="1096:45162">
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

export default GeneralSettingsPanel;
