import { createRoot, StrictMode } from '@wordpress/element';
import App from './App';
import { relocateWpAdminNotices } from './utils/relocateWpAdminNotices';
import './styles.css';

const mount = document.getElementById( 'post-smtp-admin-root' );
if ( mount ) {
	relocateWpAdminNotices();
	const root = createRoot( mount );
	root.render(
		<StrictMode>
			<App />
		</StrictMode>
	);
}
