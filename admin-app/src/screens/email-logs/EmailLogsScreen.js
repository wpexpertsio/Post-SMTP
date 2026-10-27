import { useCallback, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import QueryState from '../../components/ui/QueryState';
import FormNotice from '../connections/components/FormNotice';
import {
	filtersToQueryParams,
	hasActiveFilters,
	parseFiltersFromSearchParams,
	resetFilters,
} from './model/emailLogFilters';
import useEmailLogs from './hooks/useEmailLogs';
import useEmailLogDetail from './hooks/useEmailLogDetail';
import useEmailLogSettings from './hooks/useEmailLogSettings';
import useEmailLogMutations from './hooks/useEmailLogMutations';
import EmailLogsToolbar from './components/EmailLogsToolbar';
import EmailLogsTable from './components/EmailLogsTable';
import EmailLogsPagination from './components/EmailLogsPagination';
import EmailLogsEmptyState from './components/EmailLogsEmptyState';
import EmailLogDrawer from './components/EmailLogDrawer';
import ConfirmDialog from './components/ConfirmDialog';

const EmailLogsScreen = () => {
	const [ searchParams, setSearchParams ] = useSearchParams();
	const filters = useMemo( () => parseFiltersFromSearchParams( searchParams ), [ searchParams ] );
	const logsQuery = useEmailLogs( filters );
	const settingsQuery = useEmailLogSettings();
	const detailQuery = useEmailLogDetail( filters.logId );
	const { remove, resend, exportLogs } = useEmailLogMutations();

	const [ selectedIds, setSelectedIds ] = useState( [] );
	const [ notice, setNotice ] = useState( null );
	const [ confirm, setConfirm ] = useState( null );

	const settings = settingsQuery.data || {};
	const data = logsQuery.data || {};
	let items = data.items || [];
	if ( ! settings.show_queued ) {
		items = items.filter( ( row ) => row.status !== 'queued' );
	}

	const total = data.total || 0;
	const toolbarSettings = {
		...settings,
		facetsMailers: data.facets?.mailers || [],
		facetsForms: data.facets?.forms || [],
	};

	const patchFilters = useCallback(
		( patch ) => {
			const next = { ...filters, ...patch };
			setSearchParams( filtersToQueryParams( next ), { replace: true } );
		},
		[ filters, setSearchParams ]
	);

	const openLog = ( id ) => patchFilters( { logId: String( id ) } );
	const closeDrawer = () => patchFilters( { logId: '' } );

	const toggleRow = ( id ) => {
		setSelectedIds( ( prev ) =>
			prev.includes( id ) ? prev.filter( ( x ) => x !== id ) : [ ...prev, id ]
		);
	};

	const toggleAll = ( checked, pageIds ) => {
		if ( ! checked ) {
			setSelectedIds( ( prev ) => prev.filter( ( id ) => ! pageIds.includes( id ) ) );
			return;
		}
		setSelectedIds( ( prev ) => [ ...new Set( [ ...prev, ...pageIds ] ) ] );
	};

	const handleExport = async ( format ) => {
		setNotice( null );
		try {
			await exportLogs.mutateAsync( {
				format,
				ids: selectedIds,
				filters,
			} );
		} catch ( err ) {
			setNotice( { type: 'error', message: err.message } );
		}
	};

	const runDelete = async ( ids ) => {
		setNotice( null );
		try {
			await remove.mutateAsync( ids );
			setSelectedIds( ( prev ) => prev.filter( ( id ) => ! ids.includes( id ) ) );
			if ( filters.logId && ids.includes( Number( filters.logId ) ) ) {
				closeDrawer();
			}
			setNotice( { type: 'success', message: 'Logs deleted.' } );
		} catch ( err ) {
			setNotice( { type: 'error', message: err.message } );
		} finally {
			setConfirm( null );
		}
	};

	const handleResend = async ( id ) => {
		setNotice( null );
		try {
			const result = await resend.mutateAsync( id );
			setNotice( { type: 'success', message: result.message || 'Email resent.' } );
		} catch ( err ) {
			setNotice( { type: 'error', message: err.message } );
		}
	};

	const emptyFiltered = total === 0 && hasActiveFilters( filters );
	const emptyAll = total === 0 && ! hasActiveFilters( filters );
	const showingCount = items.length;
	const showingLabel = `Showing ${ showingCount.toLocaleString() } of ${ total.toLocaleString() }`;

	return (
		<div className="ps-el-page" data-figma-node="672:6333">
			{ notice && (
				<FormNotice
					type={ notice.type }
					message={ notice.message }
					onDismiss={ () => setNotice( null ) }
				/>
			) }

			<div className="ps-el-panel">
				<div className="ps-el-panel__head">
					<h1 className="ps-el-panel__title">Email Log</h1>
					<div className="ps-el-panel__meta">
						{ hasActiveFilters( filters ) && (
							<button
								type="button"
								className="ps-el-panel__clear"
								onClick={ () => patchFilters( resetFilters( filters ) ) }
							>
								Clear filters
							</button>
						) }
						<span className="ps-el-panel__count">{ showingLabel }</span>
					</div>
				</div>

				<EmailLogsToolbar
					filters={ filters }
					onChange={ patchFilters }
					onExport={ handleExport }
					onDelete={ () =>
						setConfirm( {
							title: 'Delete selected logs?',
							message: `This will permanently delete ${ selectedIds.length } log(s).`,
							ids: selectedIds,
						} )
					}
					settings={ toolbarSettings }
					exportBusy={ exportLogs.isPending }
					deleteDisabled={ selectedIds.length === 0 || remove.isPending }
				/>

				<QueryState isLoading={ logsQuery.isLoading } isError={ logsQuery.isError } error={ logsQuery.error }>
					{ emptyAll || emptyFiltered ? (
						<EmailLogsEmptyState filtered={ emptyFiltered } />
					) : (
						<>
							<EmailLogsTable
								items={ items }
								selectedIds={ selectedIds }
								onToggleRow={ toggleRow }
								onToggleAll={ toggleAll }
								onOpen={ openLog }
								onResend={ handleResend }
								onDeleteRow={ ( id ) =>
									setConfirm( {
										title: 'Delete this log?',
										message: 'This action cannot be undone.',
										ids: [ id ],
									} )
								}
								showOpened={ settings.show_opened_column }
								showAttachments={ settings.show_attachments }
							/>
							<EmailLogsPagination
								page={ filters.page }
								perPage={ filters.perPage }
								total={ total }
								onChange={ patchFilters }
							/>
						</>
					) }
				</QueryState>
			</div>

			<EmailLogDrawer
				open={ Boolean( filters.logId ) }
				log={ detailQuery.data }
				loading={ detailQuery.isLoading }
				onClose={ closeDrawer }
				onResend={ handleResend }
				onDelete={ ( id ) =>
					setConfirm( {
						title: 'Delete this log?',
						message: 'This action cannot be undone.',
						ids: [ id ],
					} )
				}
				resendBusy={ resend.isPending }
				deleteBusy={ remove.isPending }
				settings={ settings }
			/>

			<ConfirmDialog
				open={ Boolean( confirm ) }
				title={ confirm?.title }
				message={ confirm?.message }
				confirmLabel="Delete"
				busy={ remove.isPending }
				onCancel={ () => setConfirm( null ) }
				onConfirm={ () => confirm && runDelete( confirm.ids ) }
			/>
		</div>
	);
};

export default EmailLogsScreen;
