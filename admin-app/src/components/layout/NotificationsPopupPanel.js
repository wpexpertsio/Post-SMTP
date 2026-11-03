import { Link } from 'react-router-dom';
import iconNotifyInfo from '../../assets/brand/icon-notify-info.svg';
import iconNotifySettings from '../../assets/brand/icon-notify-settings.svg';
import { useHeaderNotifications } from '../../context/HeaderNotificationsContext';

const NotificationsPopupPanel = ( { onNavigate } ) => {
	const { items, loading, loadError } = useHeaderNotifications();

	return (
		<div
			className="ps-v2-notify-popup"
			data-figma-node="985:17939"
			role="dialog"
			aria-label="Notifications"
		>
			<header className="ps-v2-notify-popup__head">
				<h2 className="ps-v2-notify-popup__title">Notifications</h2>
				<Link
					to="/notifications/admin_email"
					className="ps-v2-notify-popup__settings"
					aria-label="Notification settings"
					onClick={ onNavigate }
				>
					<img
						src={ iconNotifySettings }
						alt=""
						width="14"
						height="15"
					/>
				</Link>
			</header>

			<div className="ps-v2-notify-popup__body">
				{ loading && (
					<p className="ps-v2-notify-popup__status">Loading…</p>
				) }
				{ ! loading && loadError && (
					<p className="ps-v2-notify-popup__status ps-v2-notify-popup__status--error">
						{ loadError }
					</p>
				) }
				{ ! loading && ! loadError && items.length === 0 && (
					<p className="ps-v2-notify-popup__empty">
						No failed notifications found.
					</p>
				) }
				{ ! loading &&
					items.map( ( item ) => (
						<NotificationRow
							key={ item.id }
							item={ item }
							onNavigate={ onNavigate }
						/>
					) ) }
			</div>
		</div>
	);
};

function NotificationRow( { item, onNavigate } ) {
	const { openFailedLog, dismissTransient } = useHeaderNotifications();

	const handleAction = () => {
		if ( item.kind === 'failed' && item.logId ) {
			openFailedLog( item.logId );
		}
		if ( item.kind === 'transient' ) {
			dismissTransient( item.id );
		}
		onNavigate?.();
	};

	return (
		<article
			className="ps-v2-notify-popup__item"
			data-unread={ item.unread ? 'true' : undefined }
		>
			<div className="ps-v2-notify-popup__item-inner">
				<span className="ps-v2-notify-popup__icon" aria-hidden="true">
					<img src={ iconNotifyInfo } alt="" width="15" height="15" />
				</span>
				<div className="ps-v2-notify-popup__content">
					<div className="ps-v2-notify-popup__text">
						<p className="ps-v2-notify-popup__item-title">{ item.title }</p>
						<p className="ps-v2-notify-popup__item-desc">{ item.description }</p>
					</div>
					<Link
						to={ item.actionTo }
						className="ps-v2-notify-popup__action"
						onClick={ handleAction }
					>
						{ item.actionLabel }
					</Link>
				</div>
			</div>
		</article>
	);
}

export default NotificationsPopupPanel;
