import EmailLogMailerCell from './EmailLogMailerCell';
import EmailLogStatusBadge from './EmailLogStatusBadge';
import EmailLogRowActions from './EmailLogRowActions';
import { iconRecipients } from './emailLogAssets';

const EmailLogsTable = ( {
	items,
	selectedIds,
	onToggleRow,
	onToggleAll,
	onOpen,
	onResend,
	onDeleteRow,
	showOpened,
	showAttachments,
} ) => {
	const pageIds = items.map( ( row ) => row.id );
	const allSelected = pageIds.length > 0 && pageIds.every( ( id ) => selectedIds.includes( id ) );

	const tableClass = `ps-el-table${ showOpened ? ' ps-el-table--with-opened' : '' }`;

	return (
		<div className={ tableClass } data-figma-node="807:12508">
			<div className="ps-el-table__row ps-el-table__row--head">
				<span className="ps-el-table__cell ps-el-table__cell--check">
					<input
						type="checkbox"
						className="ps-el-check"
						checked={ allSelected }
						onChange={ ( e ) => onToggleAll( e.target.checked, pageIds ) }
						aria-label="Select all on this page"
					/>
				</span>
				<span className="ps-el-table__cell ps-el-table__cell--subject">Subject</span>
				<span className="ps-el-table__cell ps-el-table__cell--recipient">Recipient (s)</span>
				<span className="ps-el-table__cell ps-el-table__cell--mailer">Mailer</span>
				<span className="ps-el-table__cell ps-el-table__cell--date">Date &amp; Time</span>
				<span className="ps-el-table__cell ps-el-table__cell--status">Status</span>
				{ showOpened && <span className="ps-el-table__cell ps-el-table__cell--opened">Opened</span> }
				<span className="ps-el-table__cell ps-el-table__cell--actions">Actions</span>
			</div>
			{ items.map( ( row ) => {
				const multiRecipients = ( row.recipients?.length || 0 ) > 1;
				return (
					<div
						key={ row.id }
						className="ps-el-table__row ps-el-table__row--body"
						onClick={ () => onOpen( row.id ) }
						role="button"
						tabIndex={ 0 }
						onKeyDown={ ( e ) => {
							if ( e.key === 'Enter' || e.key === ' ' ) {
								e.preventDefault();
								onOpen( row.id );
							}
						} }
					>
						<span className="ps-el-table__cell ps-el-table__cell--check" onClick={ ( e ) => e.stopPropagation() }>
							<input
								type="checkbox"
								className="ps-el-check"
								checked={ selectedIds.includes( row.id ) }
								onChange={ () => onToggleRow( row.id ) }
								aria-label={ `Select log ${ row.id }` }
							/>
						</span>
						<span className="ps-el-table__cell ps-el-table__cell--subject">
							<span className="ps-el-table__subject">{ row.subject || '(no subject)' }</span>
							{ showAttachments && row.has_attachments && (
								<span className="ps-el-chip">Attachment</span>
							) }
						</span>
						<span className="ps-el-table__cell ps-el-table__cell--recipient">
							{ multiRecipients && (
								<img className="ps-el-table__recipient-icon" src={ iconRecipients } alt="" width="12" height="12" />
							) }
							<span className="ps-el-table__recipient-text">{ row.recipients_short }</span>
						</span>
						<span className="ps-el-table__cell ps-el-table__cell--mailer">
							<EmailLogMailerCell mailer={ row.mailer } route={ row.route } />
						</span>
						<span className="ps-el-table__cell ps-el-table__cell--date">{ row.sent_at_display }</span>
						<span className="ps-el-table__cell ps-el-table__cell--status">
							<EmailLogStatusBadge status={ row.status } />
						</span>
						{ showOpened && (
							<span className="ps-el-table__cell ps-el-table__cell--opened">
								{ row.opened === true ? 'Yes' : row.opened === false ? 'No' : '—' }
							</span>
						) }
						<span className="ps-el-table__cell ps-el-table__cell--actions" onClick={ ( e ) => e.stopPropagation() }>
							<EmailLogRowActions
								onView={ () => onOpen( row.id ) }
								onResend={ () => onResend( row.id ) }
								onDelete={ () => onDeleteRow( row.id ) }
								resendDisabled={ ! row.has_body }
							/>
						</span>
					</div>
				);
			} ) }
		</div>
	);
};

export default EmailLogsTable;
