import { useMutation, useQueryClient } from '@tanstack/react-query';
import { deleteEmailLogs, exportEmailLogs, resendEmailLog } from '../api/emailLogs';

function downloadExport( payload ) {
	if ( ! payload?.content ) {
		return;
	}
	const blob = new Blob( [ payload.content ], { type: payload.mime || 'text/plain' } );
	const url = URL.createObjectURL( blob );
	const link = document.createElement( 'a' );
	link.href = url;
	link.download = payload.filename || 'email-logs.csv';
	link.click();
	URL.revokeObjectURL( url );
}

export default function useEmailLogMutations() {
	const queryClient = useQueryClient();
	const invalidate = () => {
		queryClient.invalidateQueries( { queryKey: [ 'email-logs' ] } );
		queryClient.invalidateQueries( { queryKey: [ 'email-log' ] } );
	};

	const remove = useMutation( {
		mutationFn: ( ids ) => deleteEmailLogs( ids ),
		onSuccess: () => invalidate(),
	} );

	const resend = useMutation( {
		mutationFn: ( id ) => resendEmailLog( id ),
		onSuccess: () => invalidate(),
	} );

	const exportLogs = useMutation( {
		mutationFn: ( args ) => exportEmailLogs( args ),
		onSuccess: ( data ) => downloadExport( data ),
	} );

	return { remove, resend, exportLogs };
}
