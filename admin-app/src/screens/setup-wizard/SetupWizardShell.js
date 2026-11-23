import { useState } from '@wordpress/element';
import { useNavigate } from 'react-router-dom';
import QueryState from '../../components/ui/QueryState';
import WizardProgress from './components/WizardProgress';
import { SetupWizardProvider, useSetupWizardContext } from './SetupWizardContext';
import StepChooseMailer from './StepChooseMailer';
import StepConfigure from './StepConfigure';
import StepSendTest from './StepSendTest';
import StepAllSet from './StepAllSet';

const SetupWizardBody = () => {
	const navigate = useNavigate();
	const { step, maxStep, goToStep, wizard } = useSetupWizardContext();
	const { bootstrapQuery } = wizard;
	const [ showAllSet, setShowAllSet ] = useState( false );

	const handleExit = async () => {
		try {
			await wizard.discardDraftMutation.mutateAsync();
		} catch {
			// still navigate away
		}
		navigate( '/' );
	};

	let content = null;
	if ( showAllSet || step >= 4 ) {
		content = <StepAllSet />;
	} else if ( step === 1 ) {
		content = <StepChooseMailer />;
	} else if ( step === 2 ) {
		content = <StepConfigure />;
	} else {
		content = <StepSendTest onAdvance={ () => setShowAllSet( true ) } />;
	}

	return (
		<div className="ps-app ps-app--setup-wizard">
			<header className="ps-setup-wizard__top">
				<div className="ps-setup-wizard__brand">
					<strong>Post SMTP Setup Wizard</strong>
				</div>
				<button type="button" className="ps-setup-wizard__exit" onClick={ handleExit }>
					Exit Setup Wizard
				</button>
			</header>

			<main className="ps-setup-wizard__main">
				<QueryState
					isLoading={ bootstrapQuery.isLoading }
					isError={ bootstrapQuery.isError }
					error={ bootstrapQuery.error }
				>
					{ ! showAllSet && step <= 3 && (
						<WizardProgress current={ step } maxReached={ maxStep } onSelect={ goToStep } />
					) }
					{ content }
				</QueryState>
			</main>
		</div>
	);
};

const SetupWizardShell = () => (
	<SetupWizardProvider>
		<SetupWizardBody />
	</SetupWizardProvider>
);

export default SetupWizardShell;