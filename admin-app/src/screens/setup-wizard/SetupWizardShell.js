import { useState } from '@wordpress/element';
import { useQueryClient } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import QueryState from '../../components/ui/QueryState';
import WizardSetupHeader from './components/WizardSetupHeader';
import {
	SetupWizardProvider,
	useSetupWizardContext,
	WIZARD_SKIP_DRAFT_RESTORE_KEY,
} from './SetupWizardContext';
import StepChooseMailer from './StepChooseMailer';
import StepConfigure from './StepConfigure';
import StepSendTest from './StepSendTest';
import StepAllSet from './StepAllSet';

const SetupWizardBody = () => {
	const navigate = useNavigate();
	const queryClient = useQueryClient();
	const { step, maxStep, goToStep, wizard } = useSetupWizardContext();
	const { bootstrapQuery } = wizard;
	const [ showAllSet, setShowAllSet ] = useState( false );

	const handleExit = () => {
		try {
			window.sessionStorage.setItem( WIZARD_SKIP_DRAFT_RESTORE_KEY, '1' );
		} catch {
			// sessionStorage unavailable
		}
		queryClient.setQueryData( [ 'setup-wizard' ], ( old ) => {
			if ( ! old || typeof old !== 'object' ) {
				return old;
			}
			return { ...old, draft: null };
		} );
		navigate( '/' );
		wizard.discardDraftMutation.mutate();
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

	const isAllSet = showAllSet || step >= 4;

	return (
		<div className="ps-app ps-app--setup-wizard" data-figma-node="693:20457">
			<WizardSetupHeader
				figmaNode={ isAllSet ? '693:22064' : '693:20458' }
				onExit={ handleExit }
				current={ step }
				maxReached={ maxStep }
				onSelectStep={ isAllSet ? undefined : goToStep }
				allStepsComplete={ isAllSet }
			/>

			<main className="ps-setup-wizard__stage">
				<QueryState
					isLoading={ bootstrapQuery.isLoading }
					isError={ bootstrapQuery.isError }
					error={ bootstrapQuery.error }
				>
					<div className="ps-setup-wizard__stage-inner">{ content }</div>
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