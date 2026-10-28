import iconCheckFail from '../../../assets/tools/icon-check-fail.svg';
import iconCheckPass from '../../../assets/tools/icon-check-pass.svg';
import iconCheckWarn from '../../../assets/tools/icon-check-warn.svg';

const STATUS_META = {
	passed: {
		className: 'is-pass',
		icon: iconCheckPass,
	},
	warning: {
		className: 'is-warn',
		icon: iconCheckWarn,
	},
	failed: {
		className: 'is-fail',
		icon: iconCheckFail,
	},
	unavailable: {
		className: 'is-neutral',
		icon: iconCheckWarn,
	},
	skipped: {
		className: 'is-neutral',
		icon: iconCheckWarn,
	},
};

const ConnectionCheckResultRow = ( { check } ) => {
	const status = check?.status || 'unavailable';
	const meta = STATUS_META[ status ] || STATUS_META.unavailable;

	return (
		<div
			className={ `ps-v2-tools-check-row ${ meta.className }` }
			data-status={ status }
		>
			<img className="ps-v2-tools-check-row__icon" src={ meta.icon } alt="" width="20" height="20" />
			<div className="ps-v2-tools-check-row__body">
				<p className="ps-v2-tools-check-row__title">{ check.title }</p>
				{ check.detail && (
					<p className="ps-v2-tools-check-row__detail">{ check.detail }</p>
				) }
			</div>
		</div>
	);
};

export default ConnectionCheckResultRow;
