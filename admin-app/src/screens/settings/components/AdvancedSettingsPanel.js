import { useEffect, useState } from '@wordpress/element';
import iconTimeouts from '../../../assets/settings/icon-advanced-timeouts.svg';
import iconServer from '../../../assets/settings/icon-advanced-server.svg';
import iconMessage from '../../../assets/settings/icon-advanced-message.svg';
import iconHeaders from '../../../assets/settings/icon-advanced-headers.svg';
import FormNotice from '../../connections/components/FormNotice';
import useSettingsAdvanced from '../hooks/useSettingsAdvanced';
import SettingsSectionCard from './SettingsSectionCard';
import SettingsSectionHeader from './SettingsSectionHeader';
import SettingsSelectField from './SettingsSelectField';
import SettingsToggleRow from './SettingsToggleRow';

const SectionIcon = ( { src } ) => (
	<span className="ps-v2-settings-section-head__icon-box ps-v2-settings-section-head__icon-box--lg ps-v2-settings-section-head__icon-box--white">
		<img src={ src } alt="" width="24" height="24" />
	</span>
);

const SettingsNumberField = ( { label, hint, value, onChange, min = 0 } ) => (
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
			onChange={ ( e ) => onChange( parseInt( e.target.value, 10 ) || 0 ) }
		/>
	</div>
);

const SettingsTextField = ( { label, hint, value, onChange } ) => (
	<div className="ps-v2-settings-field">
		<div className="ps-v2-settings-field__head">
			<span className="ps-v2-settings-field__label">{ label }</span>
			{ hint && <p className="ps-v2-settings-field__hint">{ hint }</p> }
		</div>
		<input
			type="text"
			className="ps-v2-settings-input"
			value={ value }
			onChange={ ( e ) => onChange( e.target.value ) }
		/>
	</div>
);

const AdvancedSettingsPanel = () => {
	const { query, saveMutation } = useSettingsAdvanced();
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

	const logLevelChoices = ( query.data?.log_level_choices || [] ).map( ( opt ) => ( {
		value: String( opt.value ),
		label: opt.label,
	} ) );

	return (
		<div className="ps-v2-settings-advanced" data-figma-node="672:7526">
			{ notice && (
				<FormNotice
					type={ notice.type }
					message={ notice.message }
					onDismiss={ () => setNotice( null ) }
				/>
			) }

			<div className="ps-v2-settings-advanced__block" data-figma-node="672:7527">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconTimeouts } /> }
					title="Timeouts"
					description="Raise these on slow hosting."
					figmaNode="672:7533"
				/>
				<SettingsSectionCard figmaNode="672:7537" className="ps-v2-settings-section--flat">
					<SettingsNumberField
						label="Connection timeout (seconds)"
						hint="Wait time to reach your mail server."
						value={ form.connection_timeout }
						onChange={ ( v ) => patch( 'connection_timeout', v ) }
					/>
					<SettingsNumberField
						label="Read timeout (seconds)"
						hint="Wait time for a reply."
						value={ form.read_timeout }
						onChange={ ( v ) => patch( 'read_timeout', v ) }
					/>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-advanced__block" data-figma-node="672:7549">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconServer } /> }
					title="Server environment"
					description="Host-level options."
					figmaNode="672:7558"
				/>
				<SettingsSectionCard figmaNode="672:7562" className="ps-v2-settings-section--flat">
					<SettingsTextField
						label="Temporary directory"
						hint="Leave as-is unless your host says otherwise."
						value={ form.temporary_directory }
						onChange={ ( v ) => patch( 'temporary_directory', v ) }
					/>
					<SettingsSelectField
						label="PHP log level"
						hint="How much detail to log."
						value={ String( form.log_level ) }
						options={ logLevelChoices }
						onChange={ ( v ) => patch( 'log_level', parseInt( v, 10 ) ) }
					/>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-advanced__block" data-figma-node="672:7574">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconMessage } /> }
					title="Message handling"
					description="Fixes for badly formatted email."
					figmaNode="672:7581"
				/>
				<SettingsSectionCard figmaNode="672:7585" className="ps-v2-settings-section--flat">
					<SettingsToggleRow
						id="broken-email-fix"
						label="Broken email fix (rebuild MIME structure)"
						checked={ form.broken_email_fix }
						onChange={ ( v ) => patch( 'broken_email_fix', v ) }
					/>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-advanced__block" data-figma-node="672:7592">
				<SettingsSectionHeader
					icon={ <SectionIcon src={ iconHeaders } /> }
					title="Custom headers"
					description="Added to every email you send."
					figmaNode="672:7599"
				/>
				<SettingsSectionCard figmaNode="672:7603" className="ps-v2-settings-section--flat">
					<div className="ps-v2-settings-field">
						<div className="ps-v2-settings-field__head">
							<span className="ps-v2-settings-field__label">Headers</span>
							<p className="ps-v2-settings-field__hint">One per line.</p>
						</div>
						<textarea
							className="ps-v2-settings-input ps-v2-settings-textarea"
							value={ form.custom_headers }
							onChange={ ( e ) => patch( 'custom_headers', e.target.value ) }
							rows={ 5 }
						/>
					</div>
				</SettingsSectionCard>
			</div>

			<div className="ps-v2-settings-save-wrap" data-figma-node="672:7608">
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

export default AdvancedSettingsPanel;
