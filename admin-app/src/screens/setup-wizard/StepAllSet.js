import { useEffect, useRef, useState } from '@wordpress/element';
import { Link, useNavigate } from 'react-router-dom';
import { getAdminUrls } from '../../config/admin';
import iconContinueArrow from '../../assets/setup-wizard/icon-continue-arrow.svg';
import iconExternalLink from '../../assets/dashboard/connections/helper/icon-external-link.svg';
import { mailerConnectionIcon } from '../connections/model/mailerConnectionIcons';
import FormNotice from '../connections/components/FormNotice';
import WizardMobileAppPromo from './components/WizardMobileAppPromo';
import { wizardAuthTypeLabel } from './model/wizardAuthTypeLabel';
import { useSetupWizardContext } from './SetupWizardContext';

const StepAllSet = () => {
	const navigate = useNavigate();
	const urls = getAdminUrls();
	const { wizard, bootstrap, draftMeta, provider } = useSetupWizardContext();
	const [ notice, setNotice ] = useState( null );
	const commitStarted = useRef( false );

	const mailerLabel =
		bootstrap?.catalog?.items?.find( ( item ) => item.slug === provider )?.label || provider || 'Mailer';
	const mailerIcon = mailerConnectionIcon( provider );
	const authType = wizardAuthTypeLabel( provider );
	const testDelivered = Boolean( draftMeta?.test_passed );

	const commitConnection = async () => {
		setNotice( null );
		await wizard.completeMutation.mutateAsync();
	};

	const finishAndNavigate = async ( to ) => {
		setNotice( null );
		try {
			if ( ! wizard.completeMutation.isSuccess ) {
				await wizard.completeMutation.mutateAsync();
			}
			navigate( to );
		} catch ( err ) {
			setNotice( { type: 'error', message: err?.message || 'Could not complete setup.' } );
		}
	};

	useEffect( () => {
		if ( ! testDelivered || commitStarted.current || wizard.completeMutation.isPending ) {
			return;
		}
		commitStarted.current = true;
		commitConnection().catch( ( err ) => {
			commitStarted.current = false;
			setNotice( {
				type: 'error',
				message: err?.message || 'Could not save your connection.',
			} );
		} );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ testDelivered ] );

	const pending = wizard.completeMutation.isPending;

	return (
		<div className="ps-setup-wizard__step4" data-figma-node="693:22140">
			<article className="ps-setup-wizard__card ps-setup-wizard__card--step-4" data-figma-node="693:22141">
				<div className="ps-setup-wizard__step4-main" data-figma-node="753:10284">
					<header className="ps-setup-wizard__step-intro" data-figma-node="693:22142">
						<div className="ps-setup-wizard__step-kicker ps-setup-wizard__step-kicker--caps">
							All set
						</div>
						<h1 className="ps-setup-wizard__step-title">You&apos;re all set</h1>
						<p className="ps-setup-wizard__step-lead ps-setup-wizard__step-lead--center">
							Your mailer is connected and your test email went through. Every message is now logged so
							you can verify delivery and debug in one place.
						</p>
					</header>

					<FormNotice type={ notice?.type } message={ notice?.message } onDismiss={ () => setNotice( null ) } />

					<div className="ps-setup-wizard__step4-body" data-figma-node="693:22147">
						<div className="ps-setup-wizard__summary-strip" data-figma-node="753:10202">
							<div className="ps-setup-wizard__summary-strip-icon" aria-hidden="true">
								{ mailerIcon ? <img src={ mailerIcon } alt="" width="22" height="22" /> : null }
							</div>
							<div className="ps-setup-wizard__summary-strip-meta">
								<div className="ps-setup-wizard__summary-strip-group">
									<strong className="ps-setup-wizard__summary-strip-label">{ mailerLabel }</strong>
									<span className="ps-setup-wizard__pill ps-setup-wizard__pill--primary">
										Primary Connection
									</span>
								</div>
								<div className="ps-setup-wizard__summary-strip-group">
									<strong className="ps-setup-wizard__summary-strip-label">{ authType }</strong>
									<span className="ps-setup-wizard__pill ps-setup-wizard__pill--auth">Auth type</span>
								</div>
								<div className="ps-setup-wizard__summary-strip-group">
									<strong className="ps-setup-wizard__summary-strip-label">Test email</strong>
									<span
										className={ `ps-setup-wizard__pill ps-setup-wizard__pill--${
											testDelivered ? 'delivered' : 'pending'
										}` }
									>
										{ testDelivered ? 'Delivered' : 'Pending' }
									</span>
								</div>
							</div>
						</div>

						<div className="ps-setup-wizard__step4-actions" data-figma-node="753:10230">
							<Link
								to="/connections"
								className="ps-setup-wizard__link-out"
								onClick={ ( e ) => {
									e.preventDefault();
									if ( ! pending ) {
										finishAndNavigate( '/connections' );
									}
								} }
							>
								<span>View Connections</span>
								<img src={ iconExternalLink } alt="" width="13" height="13" aria-hidden="true" />
							</Link>
							<button
								type="button"
								className="ps-setup-wizard__btn-continue"
								disabled={ pending }
								onClick={ () => finishAndNavigate( '/' ) }
							>
								{ pending ? 'Finishing…' : 'Go to Dashboard' }
								<img
									className="ps-setup-wizard__btn-continue-icon"
									src={ iconContinueArrow }
									alt=""
									width="16"
									height="12"
									aria-hidden="true"
								/>
							</button>
						</div>
					</div>
				</div>

				<div className="ps-setup-wizard__review-row" data-figma-node="753:10283">
					<span className="ps-setup-wizard__review-stars ps-stars" aria-hidden="true">
						★★★★★
					</span>
					<p className="ps-setup-wizard__review-copy">
						<span className="ps-setup-wizard__review-meta">Rated 4.8 · 1,200+ reviews · </span>
						<a href={ urls.review } target="_blank" rel="noreferrer">
							Leave a quick review
						</a>
					</p>
				</div>
			</article>

			<WizardMobileAppPromo />
		</div>
	);
};

export default StepAllSet;