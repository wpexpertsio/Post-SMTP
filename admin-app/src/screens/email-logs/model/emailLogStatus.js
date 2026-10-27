import statusCheck from '../../../assets/dashboard/status-check.svg';
import statusFail from '../../../assets/dashboard/status-fail.svg';
import statusQueued from '../../../assets/dashboard/status-queued.svg';

export const STATUS_BADGES = {
	success: {
		label: 'Success',
		className: 'ps-el-status--success',
		icon: statusCheck,
	},
	failed: {
		label: 'Failed',
		className: 'ps-el-status--failed',
		icon: statusFail,
	},
	queued: {
		label: 'Queued',
		className: 'ps-el-status--queued',
		icon: statusQueued,
	},
};

export function getStatusBadge( status ) {
	return STATUS_BADGES[ status ] || STATUS_BADGES.failed;
}
