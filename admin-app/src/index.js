import { createRoot, StrictMode } from '@wordpress/element';
import App from './App';
import './styles.css';

const mount = document.getElementById( 'post-smtp-admin-root' );
if ( mount ) {
	const root = createRoot( mount );
	root.render(
		<StrictMode>
			<App />
		</StrictMode>
	);
}
