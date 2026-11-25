const STEPS = [ 1, 2, 3 ];

const WizardProgress = ( { current, maxReached, onSelect, allStepsComplete } ) => (
	<nav
		className={ `ps-setup-wizard__stepper${ allStepsComplete ? ' is-all-complete' : '' }` }
		aria-label="Setup progress"
		data-figma-node={ allStepsComplete ? '693:22128' : undefined }
	>
		<ol className="ps-setup-wizard__stepper-list">
			{ STEPS.map( ( step, index ) => {
				const isActive = ! allStepsComplete && current === step;
				const isDone = allStepsComplete || current > step;
				const canVisit = step <= maxReached;
				return (
					<li key={ step } className="ps-setup-wizard__stepper-item">
						<button
							type="button"
							className={ `ps-setup-wizard__step-dot${
								isActive ? ' is-active' : ''
							}${ isDone ? ' is-done' : '' }` }
							disabled={ ! canVisit || allStepsComplete }
							onClick={ () => onSelect?.( step ) }
							aria-current={ isActive ? 'step' : undefined }
							aria-label={ `Step ${ step }` }
						>
							{ step }
						</button>
						{ index < STEPS.length - 1 && (
							<span className="ps-setup-wizard__step-line" aria-hidden="true" />
						) }
					</li>
				);
			} ) }
		</ol>
	</nav>
);

export default WizardProgress;