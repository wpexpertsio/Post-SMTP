import { useMutation, useQuery } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

export default function useSendTestEmail() {
	const bootstrapQuery = useQuery( {
		queryKey: [ 'tools', 'send-test-email' ],
		queryFn: () => apiFetch( 'tools/send-test-email' ),
	} );

	const sendMutation = useMutation( {
		mutationFn: ( payload ) =>
			apiFetch( 'tools/send-test-email', {
				method: 'POST',
				data: payload,
			} ),
	} );

	const previewMutation = useMutation( {
		mutationFn: ( payload ) =>
			apiFetch( 'tools/send-test-email/preview', {
				method: 'POST',
				data: payload,
			} ),
	} );

	return {
		bootstrapQuery,
		sendMutation,
		previewMutation,
	};
}
