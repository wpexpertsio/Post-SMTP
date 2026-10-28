import { useState } from '@wordpress/element';
import { useNavigate } from 'react-router-dom';
import FormNotice from '../../connections/components/FormNotice';
import iconResetBtn from '../../../assets/tools/reset-plugin/icon-reset-btn.svg';
import useResetPlugin from '../hooks/useResetPlugin';
import { TOOLS_NAV_ICONS } from '../toolsNavAssets';

const ResetPluginPanel = () => {
	const navigate = useNavigate();
	const { bootstrapQuery, resetMutation } = useResetPlugin();
	const [ confirmed, setConfirmed ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	const data = bootstrapQuery.data;
	const copy = data?.copy || {};

	if ( bootstrapQuery.isLoading ) {
		return <p className="ps-v2-tools-loading">Loading…</p>;
	}

	if ( bootstrapQuery.isError ) {
		return <FormNotice type="error" message="Could not load Reset Plugin settings." />;
	}

	const handleReset = async () => {
		setNotice( null );
		if ( ! confirmed ) {
			setNotice( {
				type: 'error',
				message: copy.confirm_required || 'Check the box to confirm before resetting.',
			} );
			return;
		}

		try {
			const result = await resetMutation.mutateAsync( { confirmed: true } );
			setNotice( {
				type: 'success',
				message: result?.message || copy.reset_success || 'Plugin data was removed.',
			} );
			setConfirmed( false );
			window.setTimeout( () => {
				navigate( '/connections' );
			}, 800 );
		} catch ( err ) {
			setNotice( {
				type: 'error',
				message: err?.message || copy.reset_error || 'Could not reset the plugin.',
			} );
		}
	};

	return (
		<div className="ps-v2-tools-reset" data-figma-node="564:10646">
			<header className="ps-v2-tools-panel__hero" data-figma-node="564:10648">
				<div className="ps-v2-tools-panel__hero-icon" aria-hidden="true">
					<img src={ TOOLS_NAV_ICONS[ 'reset-plugin' ] } alt="" width="24" height="24" />
				</div>
				<div>
					<h2 className="ps-v2-tools-panel__title">{ copy.title || 'Reset Plugin' }</h2>
					<p className="ps-v2-tools-panel__lead">
						{ copy.lead || 'Clear all settings and start over.' }
					</p>
				</div>
			</header>

			<FormNotice
				type={ notice?.type }
				message={ notice?.message }
				onDismiss={ () => setNotice( null ) }
			/>

			<div className="ps-v2-tools-form-card ps-v2-tools-reset__card" data-figma-node="564:10657">
				<div className="ps-v2-tools-reset__section" data-figma-node="564:10659">
					<div className="ps-v2-form-field__head">
						<span className="ps-v2-form-field__label">{ copy.section_label || 'Reset plugin' }</span>
						<p className="ps-v2-form-field__hint">
							{ copy.section_hint ||
								'Removes Post SMTP settings and data from this site. This cannot be undone.' }
						</p>
					</div>

					<label className="ps-v2-tools-reset__confirm" data-figma-node="566:10865">
						<input
							type="checkbox"
							checked={ confirmed }
							onChange={ ( e ) => setConfirmed( e.target.checked ) }
						/>
						<span>
							{ copy.confirm_label || 'Yes, I understand this deletes my settings.' }
						</span>
					</label>

					<button
						type="button"
						className="ps-v2-tools-reset__btn"
						disabled={ resetMutation.isPending || ! confirmed }
						onClick={ handleReset }
						data-figma-node="564:10843"
					>
						<img src={ iconResetBtn } alt="" width="13" height="14" />
						{ resetMutation.isPending ? 'Resetting…' : copy.reset_button || 'Reset plugin' }
					</button>
				</div>
			</div>
		</div>
	);
};

export default ResetPluginPanel;
