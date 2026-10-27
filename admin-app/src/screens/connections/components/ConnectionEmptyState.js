import { Link } from 'react-router-dom';

const ConnectionEmptyState = () => (
	<div className="ps-v2-conn-empty">
		<p className="ps-v2-conn-empty__title">No connections yet</p>
		<p className="ps-v2-conn-empty__text">Add your first mailer to start sending email through Post SMTP.</p>
		<Link to="/connections/add" className="ps-v2-conn-add-btn ps-v2-conn-add-btn--center">
			<span className="ps-v2-conn-add-btn__icon" aria-hidden="true">+</span>
			Add Mailer
		</Link>
	</div>
);

export default ConnectionEmptyState;
