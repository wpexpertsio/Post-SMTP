import { iconDeleteRow, iconEye, iconResend } from './emailLogAssets';

const EmailLogRowActions = ( { onView, onResend, onDelete, resendDisabled } ) => (
	<div className="ps-el-row-actions">
		<button type="button" className="ps-el-row-actions__btn" onClick={ onView } aria-label="View email">
			<img src={ iconEye } alt="" width="13" height="10" />
		</button>
		<button
			type="button"
			className="ps-el-row-actions__btn ps-el-row-actions__btn--resend"
			onClick={ onResend }
			disabled={ resendDisabled }
			aria-label="Resend email"
		>
			<img src={ iconResend } alt="" width="22" height="22" />
		</button>
		<button type="button" className="ps-el-row-actions__btn" onClick={ onDelete } aria-label="Delete log">
			<img src={ iconDeleteRow } alt="" width="11" height="12" />
		</button>
	</div>
);

export default EmailLogRowActions;
