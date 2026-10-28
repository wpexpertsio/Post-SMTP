import { useRef, useState } from '@wordpress/element';
import FormNotice from '../../connections/components/FormNotice';
import iconChooseFile from '../../../assets/tools/import-export/icon-choose-file.svg';
import iconExport from '../../../assets/tools/import-export/icon-export.svg';
import iconImport from '../../../assets/tools/import-export/icon-import.svg';
import useImportExport from '../hooks/useImportExport';
import { TOOLS_NAV_ICONS } from '../toolsNavAssets';

const downloadTextFile = ( filename, content ) => {
	const blob = new Blob( [ content ], { type: 'application/json;charset=utf-8' } );
	const url = URL.createObjectURL( blob );
	const link = document.createElement( 'a' );
	link.href = url;
	link.download = filename;
	link.click();
	URL.revokeObjectURL( url );
};

const ImportExportPanel = () => {
	const { bootstrapQuery, exportMutation, importMutation } = useImportExport();
	const fileInputRef = useRef( null );

	const [ selectedFileName, setSelectedFileName ] = useState( '' );
	const [ fileContent, setFileContent ] = useState( '' );
	const [ notice, setNotice ] = useState( null );

	const data = bootstrapQuery.data;
	const copy = data?.copy || {};

	if ( bootstrapQuery.isLoading ) {
		return <p className="ps-v2-tools-loading">Loading…</p>;
	}

	if ( bootstrapQuery.isError ) {
		return <FormNotice type="error" message="Could not load Import Export settings." />;
	}

	const handleExport = async () => {
		setNotice( null );
		try {
			const result = await exportMutation.mutateAsync();
			if ( result?.content && result?.filename ) {
				downloadTextFile( result.filename, result.content );
				setNotice( {
					type: 'success',
					message: result.message || copy.export_success || 'Settings exported.',
				} );
			}
		} catch ( err ) {
			setNotice( {
				type: 'error',
				message: err?.message || copy.export_unavailable || 'Export failed.',
			} );
		}
	};

	const handleChooseFile = () => {
		fileInputRef.current?.click();
	};

	const handleFileChange = ( event ) => {
		setNotice( null );
		const file = event.target.files?.[0];
		if ( ! file ) {
			setSelectedFileName( '' );
			setFileContent( '' );
			return;
		}

		setSelectedFileName( file.name );
		const reader = new FileReader();
		reader.onload = () => {
			setFileContent( typeof reader.result === 'string' ? reader.result : '' );
		};
		reader.onerror = () => {
			setFileContent( '' );
			setNotice( { type: 'error', message: copy.invalid_file || 'The selected file could not be read.' } );
		};
		reader.readAsText( file );
	};

	const handleImport = async () => {
		setNotice( null );
		if ( ! fileContent.trim() ) {
			setNotice( { type: 'error', message: copy.no_file || 'Choose a settings file before importing.' } );
			return;
		}

		try {
			const result = await importMutation.mutateAsync( fileContent );
			setNotice( {
				type: result?.success ? 'success' : 'error',
				message: result?.message || ( result?.success ? copy.import_success : copy.import_error ),
			} );
			if ( result?.success ) {
				setSelectedFileName( '' );
				setFileContent( '' );
				if ( fileInputRef.current ) {
					fileInputRef.current.value = '';
				}
			}
		} catch ( err ) {
			setNotice( {
				type: 'error',
				message: err?.message || copy.import_error || 'Import failed.',
			} );
		}
	};

	const exportDisabled = exportMutation.isPending || ! data?.can_export || ! data?.has_settings;

	return (
		<div className="ps-v2-tools-import-export" data-figma-node="560:10350">
			<header className="ps-v2-tools-panel__hero" data-figma-node="560:10352">
				<div className="ps-v2-tools-panel__hero-icon" aria-hidden="true">
					<img src={ TOOLS_NAV_ICONS[ 'import-export' ] } alt="" width="24" height="24" />
				</div>
				<div>
					<h2 className="ps-v2-tools-panel__title">{ copy.title || 'Plugin settings' }</h2>
					<p className="ps-v2-tools-panel__lead">
						{ copy.lead || 'Download or upload your settings.' }
					</p>
				</div>
			</header>

			<FormNotice
				type={ notice?.type }
				message={ notice?.message }
				onDismiss={ () => setNotice( null ) }
			/>

			{ data?.oauth_warning && (
				<p className="ps-v2-tools-import-export__warning">{ data.oauth_warning }</p>
			) }

			<div className="ps-v2-tools-form-card ps-v2-tools-import-export__card" data-figma-node="560:10362">
				<section className="ps-v2-tools-import-export__section" data-figma-node="560:10364">
					<div className="ps-v2-form-field__head">
						<span className="ps-v2-form-field__label">{ copy.export_label || 'Export settings' }</span>
						<p className="ps-v2-form-field__hint">
							{ copy.export_hint || 'Downloads your Post SMTP configuration as a JSON file.' }
						</p>
					</div>
					<button
						type="button"
						className="ps-v2-btn ps-v2-btn--primary ps-v2-tools-import-export__icon-btn"
						disabled={ exportDisabled }
						onClick={ handleExport }
						data-figma-node="561:10553"
					>
						<img src={ iconExport } alt="" width="13" height="14" />
						{ exportMutation.isPending ? 'Exporting…' : copy.export_button || 'Export Settings (JSON)' }
					</button>
				</section>

				<section className="ps-v2-tools-import-export__section" data-figma-node="560:10370">
					<div className="ps-v2-form-field__head">
						<span className="ps-v2-form-field__label">{ copy.import_label || 'Import settings' }</span>
						<p className="ps-v2-form-field__hint">
							{ copy.import_hint ||
								'Importing overwrites every current Post SMTP setting, including your Primary Connection.' }
						</p>
					</div>
					<div className="ps-v2-tools-import-export__import-row" data-figma-node="561:10580">
						<input
							ref={ fileInputRef }
							type="file"
							className="ps-v2-tools-import-export__file-input"
							accept=".json,application/json"
							onChange={ handleFileChange }
						/>
						<button
							type="button"
							className="ps-v2-btn ps-v2-tools-import-export__choose-btn"
							onClick={ handleChooseFile }
							data-figma-node="561:10584"
						>
							<img src={ iconChooseFile } alt="" width="12" height="14" />
							{ selectedFileName || copy.choose_file || 'Choose File' }
						</button>
						<button
							type="button"
							className="ps-v2-btn ps-v2-btn--primary ps-v2-tools-import-export__icon-btn"
							disabled={ importMutation.isPending || ! data?.can_export }
							onClick={ handleImport }
							data-figma-node="561:10593"
						>
							<img src={ iconImport } alt="" width="13" height="14" />
							{ importMutation.isPending ? 'Importing…' : copy.import_button || 'Import' }
						</button>
					</div>
				</section>
			</div>
		</div>
	);
};

export default ImportExportPanel;
