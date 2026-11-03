import { createContext, useCallback, useContext, useMemo, useState } from 'react';
import {
	fetchFailedNotificationLogs,
	markNotificationOpened,
} from '../api/dashboard';

const HeaderNotificationsContext = createContext( null );

let transientId = 0;

export function HeaderNotificationsProvider( { children } ) {
	const [ failedLogs, setFailedLogs ] = useState( [] );
	const [ transientItems, setTransientItems ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ loadError, setLoadError ] = useState( '' );

	const refreshFailedLogs = useCallback( async () => {
		setLoading( true );
		setLoadError( '' );
		try {
			const result = await fetchFailedNotificationLogs();
			const logs = Array.isArray( result.logs ) ? result.logs : [];
			setFailedLogs( logs );
		} catch ( error ) {
			setLoadError( error.message || 'Could not load notifications.' );
			setFailedLogs( [] );
		} finally {
			setLoading( false );
		}
	}, [] );

	const pushTransientNotification = useCallback( ( item ) => {
		const id = `transient-${ ++transientId }`;
		setTransientItems( ( prev ) => [
			{
				id,
				kind: 'transient',
				icon: 'info',
				title: item.title,
				description: item.description,
				actionLabel: item.actionLabel || 'View settings',
				actionTo: item.actionTo || '/settings/general',
			},
			...prev,
		].slice( 0, 10 ) );
		return id;
	}, [] );

	const notifySettingsSaved = useCallback(
		( options = {} ) => {
			pushTransientNotification( {
				title: 'Settings saved successfully',
				description:
					options.description ||
					'Your SMTP configuration has been updated and is ready to use.',
				actionLabel: 'View settings',
				actionTo: options.settingsPath || '/settings/general',
			} );
		},
		[ pushTransientNotification ]
	);

	const dismissTransient = useCallback( ( id ) => {
		setTransientItems( ( prev ) => prev.filter( ( item ) => item.id !== id ) );
	}, [] );

	const openFailedLog = useCallback( async ( logId ) => {
		try {
			await markNotificationOpened( logId );
		} catch {
			// Non-blocking — user can still open the log.
		}
		setFailedLogs( ( prev ) =>
			prev.map( ( log ) =>
				log.id === logId ? { ...log, opened: true } : log
			)
		);
	}, [] );

	const items = useMemo( () => {
		const failedItems = failedLogs.map( ( log ) => {
			const subject = log.subject?.trim();
			const title = subject || 'Failed email report';
			const recipient = log.sent_to?.trim();
			const timeAgo = log.delivery_time?.trim();
			let description = 'An email failed to send.';
			if ( recipient && timeAgo ) {
				description = `Email to ${ recipient } failed ${ timeAgo } ago.`;
			} else if ( recipient ) {
				description = `Email to ${ recipient } failed to send.`;
			} else if ( timeAgo ) {
				description = `An email failed ${ timeAgo } ago.`;
			}
			if ( log.error && typeof log.error === 'string' ) {
				description = `${ description } ${ log.error }`;
			}

			return {
				id: `failed-${ log.id }`,
				kind: 'failed',
				logId: log.id,
				icon: 'info',
				title,
				description,
				actionLabel: 'View email log',
				actionTo: `/email-logs?logId=${ log.id }`,
				unread: ! log.opened,
			};
		} );

		return [ ...transientItems, ...failedItems ];
	}, [ failedLogs, transientItems ] );

	const unreadCount = useMemo( () => {
		const failedUnread = failedLogs.filter( ( log ) => ! log.opened ).length;
		return failedUnread + transientItems.length;
	}, [ failedLogs, transientItems ] );

	const value = useMemo(
		() => ( {
			items,
			loading,
			loadError,
			unreadCount,
			refreshFailedLogs,
			pushTransientNotification,
			notifySettingsSaved,
			dismissTransient,
			openFailedLog,
		} ),
		[
			items,
			loading,
			loadError,
			unreadCount,
			refreshFailedLogs,
			pushTransientNotification,
			notifySettingsSaved,
			dismissTransient,
			openFailedLog,
		]
	);

	return (
		<HeaderNotificationsContext.Provider value={ value }>
			{ children }
		</HeaderNotificationsContext.Provider>
	);
}

export function useHeaderNotifications() {
	const ctx = useContext( HeaderNotificationsContext );
	if ( ! ctx ) {
		throw new Error(
			'useHeaderNotifications must be used within HeaderNotificationsProvider'
		);
	}
	return ctx;
}
