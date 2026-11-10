import iconEmailOpened from '../../../assets/email-logs/icon-email-opened.svg';
import iconEmailNotOpened from '../../../assets/email-logs/icon-email-not-opened.svg';

const EmailLogOpenedIndicator = ( { opened } ) => {
	if ( opened !== true && opened !== false ) {
		return <span className="ps-el-opened ps-el-opened--na">—</span>;
	}

	if ( opened ) {
		return (
			<span className="ps-el-opened ps-el-opened--yes" aria-label="Opened">
				<span className="ps-el-opened__badge">
					<img src={ iconEmailOpened } alt="" width="13" height="11" />
				</span>
			</span>
		);
	}

	return (
		<span className="ps-el-opened ps-el-opened--no" aria-label="Not opened">
			<img
				className="ps-el-opened__not-opened"
				src={ iconEmailNotOpened }
				alt=""
				width="22"
				height="22"
			/>
		</span>
	);
};

export default EmailLogOpenedIndicator;
