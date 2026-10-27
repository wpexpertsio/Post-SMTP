import { useEffect, useState } from 'react';
import EmailLogsFilterSelect from './EmailLogsFilterSelect';

const PER_PAGE_OPTIONS = [
	{ value: '25', label: '25' },
	{ value: '50', label: '50' },
	{ value: '100', label: '100' },
];

const EmailLogsPagination = ( { page, perPage, total, onChange } ) => {
	const [ draftPerPage, setDraftPerPage ] = useState( String( perPage ) );

	useEffect( () => {
		setDraftPerPage( String( perPage ) );
	}, [ perPage ] );

	const currentPage = Number( page ) || 1;
	const size = Number( perPage ) || 50;
	const totalPages = Math.max( 1, Math.ceil( total / size ) );
	const canPrev = currentPage > 1;
	const canNext = currentPage < totalPages;

	const pageNumbers = [];
	const start = Math.max( 1, currentPage - 1 );
	const end = Math.min( totalPages, start + 2 );
	for ( let i = start; i <= end; i += 1 ) {
		pageNumbers.push( i );
	}

	return (
		<div className="ps-el-pagination" data-figma-node="672:6713">
			<div className="ps-el-pagination__left">
				<span className="ps-el-pagination__show-label">Show:</span>
				<EmailLogsFilterSelect
					compact
					label=""
					value={ draftPerPage }
					options={ PER_PAGE_OPTIONS }
					onChange={ setDraftPerPage }
					ariaLabel="Entries per page"
				/>
				<button
					type="button"
					className="ps-el-btn ps-el-btn--apply"
					onClick={ () => onChange( { perPage: draftPerPage, page: '1' } ) }
				>
					Apply
				</button>
			</div>
			<div className="ps-el-pagination__right">
				<button
					type="button"
					className="ps-el-pager"
					disabled={ ! canPrev }
					onClick={ () => onChange( { page: String( currentPage - 1 ) } ) }
				>
					Prev
				</button>
				{ pageNumbers.map( ( num ) => (
					<button
						key={ num }
						type="button"
						className={ `ps-el-pager ps-el-pager--num${ num === currentPage ? ' is-active' : '' }` }
						onClick={ () => onChange( { page: String( num ) } ) }
					>
						{ num }
					</button>
				) ) }
				<button
					type="button"
					className="ps-el-pager"
					disabled={ ! canNext }
					onClick={ () => onChange( { page: String( currentPage + 1 ) } ) }
				>
					Next
				</button>
			</div>
		</div>
	);
};

export default EmailLogsPagination;
