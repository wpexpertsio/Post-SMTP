import { useState } from '@wordpress/element';
import { exportEmailLogs } from '../../email-logs/api/emailLogs';
import SettingsSelectField from './SettingsSelectField';

const DEFAULT_COLUMNS = [ 'subject', 'status', 'to', 'timestamp' ];

const downloadBlob = ( { content, filename, mime } ) => {
	const blob = new Blob( [ content ], { type: mime || 'text/plain' } );
	const url = URL.createObjectURL( blob );
	const anchor = document.createElement( 'a' );
	anchor.href = url;
	anchor.download = filename || 'email-logs.csv';
	anchor.click();
	URL.revokeObjectURL( url );
};

const EmailLogsExportSection = ( { disabled, durationChoices, columnDefs } ) => {
	const [ duration, setDuration ] = useState( '30' );
	const [ format, setFormat ] = useState( 'csv' );
	const [ columns, setColumns ] = useState( () => [ ...DEFAULT_COLUMNS ] );
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( null );

	const defs = columnDefs?.length ? columnDefs : [];

	const toggleColumn = ( id ) => {
		setColumns( ( prev ) =>
			prev.includes( id ) ? prev.filter( ( c ) => c !== id ) : [ ...prev, id ]
		);
	};

	const selectAll = () => setColumns( defs.map( ( c ) => c.id ) );
	const clearAll = () => setColumns( [] );

	const handleExport = async () => {
		if ( disabled || busy || ! columns.length ) {
			return;
		}
		setError( null );
		setBusy( true );
		try {
			const result = await exportEmailLogs( {
				format,
				filters: { date: duration },
			} );
			if ( result?.content ) {
				downloadBlob( result );
			} else {
				setError( 'Export did not return any data.' );
			}
		} catch ( err ) {
			setError( err?.message || 'Export failed.' );
		} finally {
			setBusy( false );
		}
	};

	const left = defs.filter( ( _, i ) => i % 2 === 0 );
	const right = defs.filter( ( _, i ) => i % 2 === 1 );

	return (
		<div className="ps-v2-settings-export" data-figma-node="486:6226">
			<SettingsSelectField
				label="Duration"
				hint="How far back to include."
				value={ duration }
				options={ durationChoices || [] }
				disabled={ disabled }
				onChange={ setDuration }
			/>

			<div className="ps-v2-settings-export__include" data-figma-node="486:6232">
				<div className="ps-v2-settings-export__include-head">
					<div>
						<p className="ps-v2-settings-export__label">Include</p>
						<p className="ps-v2-settings-export__hint">Pick the columns to export.</p>
					</div>
					<button
						type="button"
						className="ps-v2-settings-export__link"
						disabled={ disabled }
						onClick={ selectAll }
					>
						Select all
					</button>
					<button
						type="button"
						className="ps-v2-settings-export__link ps-v2-settings-export__link--muted"
						disabled={ disabled }
						onClick={ clearAll }
					>
						Clear
					</button>
				</div>

				<div className="ps-v2-settings-export__columns">
					<div className="ps-v2-settings-export__column">
						{ left.map( ( col ) => (
							<label key={ col.id } className="ps-v2-settings-export__check">
								<input
									type="checkbox"
									checked={ columns.includes( col.id ) }
									disabled={ disabled }
									onChange={ () => toggleColumn( col.id ) }
								/>
								<span>{ col.label }</span>
							</label>
						) ) }
					</div>
					<div className="ps-v2-settings-export__column">
						{ right.map( ( col ) => (
							<label key={ col.id } className="ps-v2-settings-export__check">
								<input
									type="checkbox"
									checked={ columns.includes( col.id ) }
									disabled={ disabled }
									onChange={ () => toggleColumn( col.id ) }
								/>
								<span>{ col.label }</span>
							</label>
						) ) }
					</div>
				</div>

				<div className="ps-v2-settings-export__formats" data-figma-node="547:7480">
					<button
						type="button"
						className={ `ps-v2-settings-export__format${ format === 'csv' ? ' is-active' : '' }` }
						disabled={ disabled }
						onClick={ () => setFormat( 'csv' ) }
					>
						CSV
					</button>
					<button
						type="button"
						className={ `ps-v2-settings-export__format${ format === 'json' ? ' is-active' : '' }` }
						disabled={ disabled }
						onClick={ () => setFormat( 'json' ) }
					>
						JSON
					</button>
				</div>
			</div>

			<div className="ps-v2-settings-export__footer" data-figma-node="532:4718">
				<div>
					<p className="ps-v2-settings-export__label">Format</p>
					<p className="ps-v2-settings-export__hint">CSV opens in a spreadsheet.</p>
				</div>
				{ error && <p className="ps-v2-settings-export__error">{ error }</p> }
				<button
					type="button"
					className="ps-v2-settings-export__submit"
					disabled={ disabled || busy || ! columns.length }
					onClick={ handleExport }
				>
					{ busy ? 'Exporting…' : 'Export selected' }
				</button>
			</div>
		</div>
	);
};

export default EmailLogsExportSection;
