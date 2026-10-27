import EmailLogAccordion from './drawer/EmailLogAccordion';
import EmailLogStatusBadge from './EmailLogStatusBadge';
import EmailLogMailerCell from './EmailLogMailerCell';

const listBlock = ( label, values ) => (
	<div className="ps-el-drawer__field">
		<span className="ps-el-drawer__label">{ label }</span>
		<div className="ps-el-drawer__value">
			{ values?.length ? values.join( ', ' ) : '—' }
		</div>
	</div>
);

const EmailLogDrawer = ( {
	open,
	log,
	loading,
	onClose,
	onResend,
	onDelete,
	resendBusy,
	deleteBusy,
	settings,
} ) => {
	if ( ! open ) {
		return null;
	}

	const showAttachments = settings?.show_attachments && log?.attachments?.length;

	return (
		<div className="ps-el-drawer-backdrop" role="presentation" onClick={ onClose }>
			<aside
				className="ps-el-drawer"
				role="dialog"
				aria-modal="true"
				aria-label="Email log details"
				onClick={ ( e ) => e.stopPropagation() }
			>
				<header className="ps-el-drawer__header">
					<button type="button" className="ps-el-drawer__close" onClick={ onClose } aria-label="Close">
						×
					</button>
					{ loading || ! log ? (
						<p>Loading…</p>
					) : (
						<>
							<h2>{ log.subject || '(no subject)' }</h2>
							<div className="ps-el-drawer__meta">
								<EmailLogStatusBadge status={ log.status } />
								<span>{ log.sent_at_display }</span>
								<EmailLogMailerCell mailer={ log.mailer } route={ log.route } />
							</div>
							<div className="ps-el-drawer__actions">
								<button
									type="button"
									className="ps-el-btn ps-el-btn--primary"
									disabled={ ! log.resend_available || resendBusy }
									onClick={ () => onResend( log.id ) }
								>
									Resend
								</button>
								<button
									type="button"
									className="ps-el-btn ps-el-btn--danger"
									onClick={ () => onDelete( log.id ) }
									disabled={ deleteBusy }
								>
									Delete
								</button>
							</div>
						</>
					) }
				</header>
				{ ! loading && log && (
					<div className="ps-el-drawer__body">
						<EmailLogAccordion title="Email details" defaultOpen>
							{ listBlock( 'From', [ log.from ] ) }
							{ listBlock( 'Message-ID', log.message_id ? [ log.message_id ] : [] ) }
						</EmailLogAccordion>
						<EmailLogAccordion title="Recipients">
							{ listBlock( 'To', log.to ) }
							{ listBlock( 'Cc', log.cc ) }
							{ listBlock( 'Bcc', log.bcc ) }
						</EmailLogAccordion>
						<EmailLogAccordion title="Headers">
							<pre className="ps-el-code">{ log.headers || '—' }</pre>
						</EmailLogAccordion>
						{ showAttachments && (
							<EmailLogAccordion title="Attachments">
								<ul className="ps-el-list">
									{ log.attachments.map( ( file, index ) => (
										<li key={ index }>{ file.name || `Attachment ${ index + 1 }` }</li>
									) ) }
								</ul>
							</EmailLogAccordion>
						) }
						<EmailLogAccordion title="Transcript">
							<pre className="ps-el-code ps-el-code--dark">{ log.transcript || '—' }</pre>
						</EmailLogAccordion>
						<EmailLogAccordion title="Server response">
							<p>{ log.server_response || '—' }</p>
						</EmailLogAccordion>
						<EmailLogAccordion title="Preview" defaultOpen>
							{ log.preview_available ? (
								<iframe
									className="ps-el-preview"
									title="Email preview"
									sandbox=""
									srcDoc={ log.preview_html }
								/>
							) : (
								<p>Preview not available — message body was not stored.</p>
							) }
						</EmailLogAccordion>
					</div>
				) }
			</aside>
		</div>
	);
};

export default EmailLogDrawer;
