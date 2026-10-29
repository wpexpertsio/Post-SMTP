import { useEffect } from '@wordpress/element';
import { useNavigate } from 'react-router-dom';

const config = window.postSmtpAdmin || {};

/**
 * Maps ?ps-route= on admin.php?page=postman to HashRouter paths (WP submenu links).
 */
const AdminRouteSync = () => {
	const navigate = useNavigate();

	useEffect( () => {
		if ( ! config.usesReactAdmin ) {
			return;
		}

		const params = new URLSearchParams( window.location.search );
		const route = params.get( 'ps-route' );
		if ( ! route ) {
			return;
		}

		const path = `/${ route.replace( /^\//, '' ) }`;
		navigate( path, { replace: true } );

		params.delete( 'ps-route' );
		const query = params.toString();
		const cleanUrl =
			window.location.pathname +
			( query ? `?${ query }` : '' ) +
			window.location.hash;
		window.history.replaceState( null, '', cleanUrl );
	}, [ navigate ] );

	return null;
};

export default AdminRouteSync;
