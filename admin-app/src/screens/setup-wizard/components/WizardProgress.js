const STEPS = [
	{ id: 1, label: 'Choose mailer' },
	{ id: 2, label: 'Configure' },
	{ id: 3, label: 'Send test' },
];

const WizardProgress = ( { current, maxReached, onSelect } ) => (
	<nav className="ps-setup-wizard__progress" aria-label="Setup progress" data-figma-node="693:20458">
		<ol className="ps-setup-wizard__progress-list">
			{ STEPS.map( ( step, index ) => {
				const done = current > step.id;
				const active = current === step.id;
				const canVisit = step.id <= maxReached;
				return (
					<li
						key={ step.id }
						className={ `ps-setup-wizard__progress-item${ active ? ' is-active' : '' }${ done ? ' is-done' : '' }` }
					>
						<button
							type="button"
							className="ps-setup-wizard__progress-pill"
							disabled={ ! canVisit }
							onClick={ () => onSelect( step.id ) }
							aria-current={ active ? 'step' : undefined }
						>
							<span className="ps-setup-wizard__progress-index">{ step.id }</span>
							<span className="ps-setup-wizard__progress-label">{ step.label }</span>
						</button>
						{ index < STEPS.length - 1 && (
							<span className="ps-setup-wizard__progress-connector" aria-hidden="true" />
						) }
					</li>
				);
			} ) }
		</ol>
	</nav>
);

export default WizardProgress;