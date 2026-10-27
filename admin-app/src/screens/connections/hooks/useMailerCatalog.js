import { useQuery } from '@tanstack/react-query';
import apiFetch from '../../../api/client';

export default function useMailerCatalog() {
	return useQuery( {
		queryKey: [ 'mailer-catalog' ],
		queryFn: () => apiFetch( 'mailer-catalog' ),
	} );
}
