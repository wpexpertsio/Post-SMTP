import iconCheck from '../../../assets/mobile-app/icon-check.svg';
import useMobileApp from '../../mobile-app/hooks/useMobileApp';

const DEFAULT_FEATURES = [
	'Support multiple sites',
	'Easy email tracking & previews',
	'Quick error details',
	'Resend failed emails on the go',
];

const WizardMobileAppPromo = () => {
	const { bootstrapQuery } = useMobileApp();
	const data = bootstrapQuery.data;
	const features = data?.promo_features?.length ? data.promo_features : DEFAULT_FEATURES;

	return (
		<div className="ps-setup-wizard__mobile-card" data-figma-node="1273:18831">
			<div className="ps-setup-wizard__mobile-promo" data-figma-node="1273:18832">
				<div className="ps-setup-wizard__mobile-promo-inner">
					<div className="ps-setup-wizard__mobile-promo-copy">
						<h2 className="ps-setup-wizard__mobile-promo-title" data-figma-node="1273:18840">
							The Only WP SMTP Plugin with Its Own Mobile App
						</h2>
						<div className="ps-setup-wizard__mobile-promo-features">
							<ul>
								{ features.slice( 0, 2 ).map( ( item ) => (
									<li key={ item }>
										<img src={ iconCheck } alt="" width="14" height="14" aria-hidden="true" />
										<span>{ item }</span>
									</li>
								) ) }
							</ul>
							<ul>
								{ features.slice( 2, 4 ).map( ( item ) => (
									<li key={ item }>
										<img src={ iconCheck } alt="" width="14" height="14" aria-hidden="true" />
										<span>{ item }</span>
									</li>
								) ) }
							</ul>
						</div>
					</div>
					{ data?.qr && (
						<div className="ps-setup-wizard__mobile-promo-qr" data-figma-node="1273:18859">
							<img src={ data.qr } alt="QR code to connect the mobile app" width="212" height="212" />
						</div>
					) }
				</div>
			</div>
		</div>
	);
};

export default WizardMobileAppPromo;