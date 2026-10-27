import EmailLogsFilterSelect from './EmailLogsFilterSelect';
import { iconDelete, iconExport, iconSearch } from './emailLogAssets';

const STATUS_OPTIONS = [
	{ value: 'all', label: 'All' },
	{ value: 'success', label: 'Success' },
	{ value: 'failed', label: 'Failed' },
	{ value: 'queued', label: 'Queued' },
];

const DATE_OPTIONS = [
	{ value: 'last_7_days', label: 'Last 7 days' },
	{ value: 'last_30_days', label: 'Last 30 days' },
	{ value: 'last_90_days', label: 'Last 90 days' },
	{ value: 'all', label: 'All time' },
];

const EmailLogsToolbar = ( {
	filters,
	onChange,
	onExport,
	onDelete,
	exportBusy,
	deleteDisabled,
	settings,
} ) => {
	const showQueued = settings?.show_queued;
	const statusOptions = showQueued
		? STATUS_OPTIONS
		: STATUS_OPTIONS.filter( ( o ) => o.value !== 'queued' );

	const mailerOptions = [
		{ value: 'all', label: 'All' },
		...( settings?.facetsMailers || [] ).map( ( uri ) => ( {
			value: uri,
			label: uri.length > 24 ? `${ uri.slice( 0, 24 ) }…` : uri,
		} ) ),
	];

	const formOptions = [
		{ value: 'all', label: 'All' },
		...( settings?.facetsForms || [] ).map( ( form ) => ( { value: form, label: form } ) ),
	];

	return (
		<div className="ps-el-toolbar" data-figma-node="672:6339">
			<div className="ps-el-search">
				<img className="ps-el-search__icon" src={ iconSearch } alt="" width="13" height="13" />
				<input
					type="text"
					className="ps-el-search__input"
					placeholder="Search emails..."
					value={ filters.q }
					onChange={ ( e ) => onChange( { q: e.target.value, page: '1' } ) }
					aria-label="Search emails"
				/>
			</div>
			<div className="ps-el-toolbar__filters">
				<EmailLogsFilterSelect
					label="Status"
					value={ filters.status }
					options={ statusOptions }
					onChange={ ( status ) => onChange( { status, page: '1' } ) }
				/>
				<EmailLogsFilterSelect
					label="Mailer"
					value={ filters.mailer }
					options={ mailerOptions }
					onChange={ ( mailer ) => onChange( { mailer, page: '1' } ) }
				/>
				<EmailLogsFilterSelect
					label="Date"
					value={ filters.date }
					options={ DATE_OPTIONS }
					onChange={ ( date ) => onChange( { date, page: '1' } ) }
				/>
				{ settings?.form_filter_available && (
					<EmailLogsFilterSelect
						label="Form"
						value={ filters.form }
						options={ formOptions }
						onChange={ ( form ) => onChange( { form, page: '1' } ) }
					/>
				) }
				<button
					type="button"
					className="ps-el-btn ps-el-btn--export"
					onClick={ () => onExport( 'csv' ) }
					disabled={ exportBusy }
				>
					<img src={ iconExport } alt="" width="13" height="13" />
					Export
				</button>
				<button
					type="button"
					className="ps-el-btn ps-el-btn--export-json"
					onClick={ () => onExport( 'json' ) }
					disabled={ exportBusy }
					title="Export JSON"
					aria-label="Export JSON"
				>
					JSON
				</button>
				<button
					type="button"
					className="ps-el-btn ps-el-btn--delete"
					onClick={ onDelete }
					disabled={ deleteDisabled }
				>
					<img src={ iconDelete } alt="" width="13" height="14" />
					Delete
				</button>
			</div>
		</div>
	);
};

export default EmailLogsToolbar;
