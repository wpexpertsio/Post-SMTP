import wizardLogo from '../../../assets/brand/post-smtp-wizard-logo.svg';
import iconClose from '../../../assets/brand/icon-drawer-close.svg';
import WizardProgress from './WizardProgress';

const WizardSetupHeader = ( {
	onExit,
	current,
	maxReached,
	onSelectStep,
	allStepsComplete,
	figmaNode = '693:20458',
} ) => (
	<header className="ps-setup-wizard__header" data-figma-node={ figmaNode }>
		<div className="ps-setup-wizard__header-inner">
			<div className="ps-setup-wizard__brand-lockup" aria-label="Post SMTP">
				<img
					className="ps-setup-wizard__brand-logo"
					src={ wizardLogo }
					alt="Post SMTP"
					width="181"
					height="40"
				/>
			</div>

			<WizardProgress
				current={ current }
				maxReached={ maxReached }
				onSelect={ onSelectStep }
				allStepsComplete={ allStepsComplete }
			/>

			<button
				type="button"
				className="ps-setup-wizard__exit"
				onClick={ onExit }
				aria-label="Exit setup wizard"
				data-figma-node="693:22137"
			>
				<span className="ps-setup-wizard__exit-label">Exit Setup Wizard</span>
				<span className="ps-setup-wizard__exit-icon" aria-hidden="true">
					<img src={ iconClose } alt="" width="12" height="12" />
				</span>
			</button>
		</div>
	</header>
);

export default WizardSetupHeader;