const ConfirmDialog = ( { open, title, message, confirmLabel, onConfirm, onCancel, busy } ) => {
	if ( ! open ) {
		return null;
	}
	return (
		<div className="ps-el-dialog-backdrop" role="presentation" onClick={ onCancel }>
			<div
				className="ps-el-dialog"
				role="dialog"
				aria-modal="true"
				aria-labelledby="ps-el-dialog-title"
				onClick={ ( e ) => e.stopPropagation() }
			>
				<h3 id="ps-el-dialog-title">{ title }</h3>
				<p>{ message }</p>
				<div className="ps-el-dialog__actions">
					<button type="button" className="ps-el-btn ps-el-btn--ghost" onClick={ onCancel } disabled={ busy }>
						Cancel
					</button>
					<button type="button" className="ps-el-btn ps-el-btn--danger" onClick={ onConfirm } disabled={ busy }>
						{ confirmLabel }
					</button>
				</div>
			</div>
		</div>
	);
};

export default ConfirmDialog;
