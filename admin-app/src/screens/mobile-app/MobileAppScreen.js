import { useState } from '@wordpress/element';
import FormNotice from '../connections/components/FormNotice';
import badgeAppStore from '../../assets/mobile-app/badge-app-store.png';
import badgeGooglePlay from '../../assets/mobile-app/badge-google-play.png';
import iconBenefitCheck from '../../assets/mobile-app/icon-benefit-check.svg';
import iconCheck from '../../assets/mobile-app/icon-check.svg';
import iconCopy from '../../assets/mobile-app/icon-copy.svg';
import iconPhone from '../../assets/mobile-app/icon-phone.svg';
import iconRegenerate from '../../assets/mobile-app/icon-regenerate.svg';
import useMobileApp from './hooks/useMobileApp';

const PROMO_HEADLINE = 'The Only WP SMTP Plugin with Its Own Mobile App';

const MobileAppScreen = () => {
	const { bootstrapQuery, regenerateMutation, disconnectMutation, notificationsMutation } =
		useMobileApp();
	const [ notice, setNotice ] = useState( null );
	const [ copyLabel, setCopyLabel ] = useState( 'Copy' );

	const data = bootstrapQuery.data;

	if ( bootstrapQuery.isLoading ) {
		return <p className="ps-v2-mobile-loading">Loading…</p>;
	}

	if ( bootstrapQuery.isError ) {
		return <FormNotice type="error" message="Could not load Mobile App settings." />;
	}

	const handleCopy = async () => {
		const text = data?.secret_key || data?.connect_url || '';
		if ( ! text ) {
			return;
		}
		try {
			await navigator.clipboard.writeText( text );
			setCopyLabel( 'Copied' );
			window.setTimeout( () => setCopyLabel( 'Copy' ), 2000 );
		} catch ( err ) {
			setNotice( { type: 'error', message: 'Could not copy to clipboard.' } );
		}
	};

	const handleRegenerate = async () => {
		setNotice( null );
		try {
			const result = await regenerateMutation.mutateAsync();
			setNotice( {
				type: 'success',
				message: result?.message || 'QR code and key regenerated.',
			} );
		} catch ( err ) {
			setNotice( {
				type: 'error',
				message: err?.message || 'Could not regenerate QR code.',
			} );
		}
	};

	const handleDisconnect = async ( fcmToken ) => {
		setNotice( null );
		try {
			const result = await disconnectMutation.mutateAsync( fcmToken );
			setNotice( {
				type: result?.success ? 'success' : 'error',
				message: result?.message || 'Disconnect failed.',
			} );
		} catch ( err ) {
			setNotice( {
				type: 'error',
				message: err?.message || 'Could not disconnect device.',
			} );
		}
	};

	const handleToggleNotify = async ( device, checked ) => {
		const enabled = ( data?.devices || [] )
			.map( ( d ) => {
				if ( d.fcm_token === device.fcm_token ) {
					return checked ? d.fcm_token : null;
				}
				return d.enable_notification ? d.fcm_token : null;
			} )
			.filter( Boolean );

		try {
			await notificationsMutation.mutateAsync( enabled );
		} catch ( err ) {
			setNotice( {
				type: 'error',
				message: err?.message || 'Could not save notification preference.',
			} );
		}
	};

	return (
		<div className="ps-v2-mobile-page" data-figma-node="672:9146">
			<FormNotice
				type={ notice?.type }
				message={ notice?.message }
				onDismiss={ () => setNotice( null ) }
			/>

			<div className="ps-v2-mobile-layout">
				<div className="ps-v2-mobile-main">
					<section className="ps-v2-mobile-card ps-v2-mobile-install" data-figma-node="675:19475">
						<div className="ps-v2-mobile-install__row">
							<div className="ps-v2-mobile-install__icon" aria-hidden="true">
								<img src={ iconPhone } alt="" width="20" height="26" />
							</div>
							<div className="ps-v2-mobile-install__text">
								<h2 className="ps-v2-mobile-install__title">Install the app</h2>
								<p className="ps-v2-mobile-install__lead">Get Post SMTP on your phone.</p>
							</div>
							<div className="ps-v2-mobile-install__stores">
								<a
									href={ data?.store_links?.google_play }
									target="_blank"
									rel="noreferrer"
									className="ps-v2-mobile-store-badge"
								>
									<img src={ badgeGooglePlay } alt="Get it on Google Play" height="40" />
								</a>
								<a
									href={ data?.store_links?.app_store }
									target="_blank"
									rel="noreferrer"
									className="ps-v2-mobile-store-badge"
								>
									<img src={ badgeAppStore } alt="Download on the App Store" height="40" />
								</a>
							</div>
						</div>
					</section>

					<section className="ps-v2-mobile-card ps-v2-mobile-promo-wrap" data-figma-node="672:9177">
						<div className="ps-v2-mobile-promo" data-figma-node="936:12986">
							<div className="ps-v2-mobile-promo__inner">
								<div className="ps-v2-mobile-promo__copy">
									<h3 className="ps-v2-mobile-promo__headline">{ PROMO_HEADLINE }</h3>
									<div className="ps-v2-mobile-promo__features">
										<ul>
											{ ( data?.promo_features || [] ).slice( 0, 2 ).map( ( item ) => (
												<li key={ item }>
													<img src={ iconCheck } alt="" width="14" height="14" />
													<span>{ item }</span>
												</li>
											) ) }
										</ul>
										<ul>
											{ ( data?.promo_features || [] ).slice( 2, 4 ).map( ( item ) => (
												<li key={ item }>
													<img src={ iconCheck } alt="" width="14" height="14" />
													<span>{ item }</span>
												</li>
											) ) }
										</ul>
									</div>
								</div>
								{ data?.qr && ! data?.is_connected && (
									<div className="ps-v2-mobile-promo__qr" data-figma-node="1271:17966">
										<img src={ data.qr } alt="QR code to connect the mobile app" width="212" height="212" />
									</div>
								) }
							</div>
						</div>
					</section>

					<section className="ps-v2-mobile-card ps-v2-mobile-connect" data-figma-node="1271:17969">
						<h3 className="ps-v2-mobile-connect__title">Connect with QR</h3>

						{ data?.qr_error && ! data?.is_connected && (
							<p className="ps-v2-mobile-connect__error">{ data.qr_error }</p>
						) }

						{ data?.is_connected ? (
							<div className="ps-v2-mobile-devices">
								<p className="ps-v2-mobile-connect__hint">
									Connected devices. Disconnect to generate a new QR code.
								</p>
								{ ( data.devices || [] ).map( ( device ) => (
									<div key={ device.fcm_token } className="ps-v2-mobile-device">
										<div className="ps-v2-mobile-device__head">
											<strong>{ device.device }</strong>
											<button
												type="button"
												className="ps-v2-mobile-device__disconnect"
												disabled={ disconnectMutation.isPending }
												onClick={ () => handleDisconnect( device.fcm_token ) }
											>
												Disconnect
											</button>
										</div>
										<label className="ps-v2-mobile-device__notify">
											<input
												type="checkbox"
												checked={ Boolean( device.enable_notification ) }
												disabled={ notificationsMutation.isPending }
												onChange={ ( e ) =>
													handleToggleNotify( device, e.target.checked )
												}
											/>
											<span>Send failed email notification</span>
										</label>
									</div>
								) ) }
							</div>
						) : (
							<div className="ps-v2-mobile-connect__body">
								<div className="ps-v2-mobile-connect__block">
									<p className="ps-v2-mobile-connect__label">Scan this code in the app</p>
									<p className="ps-v2-mobile-connect__hint">
										Keep it private — anyone with it can connect.
									</p>
								</div>

								<div className="ps-v2-mobile-connect__block">
									<p className="ps-v2-mobile-connect__label">Secret Key</p>
									<p className="ps-v2-mobile-connect__hint">
										Paste this in the app if you can&apos;t scan the QR code.
									</p>
									<div className="ps-v2-mobile-secret-row">
										<div className="ps-v2-mobile-secret-field">
											<code>{ data?.secret_key || '—' }</code>
										</div>
										<button
											type="button"
											className="ps-v2-mobile-copy-btn"
											onClick={ handleCopy }
											disabled={ ! data?.secret_key }
											aria-label="Copy secret key"
										>
											<img src={ iconCopy } alt="" width="38" height="38" />
											<span className="screen-reader-text">{ copyLabel }</span>
										</button>
									</div>
								</div>

								<button
									type="button"
									className="ps-v2-btn ps-v2-btn--primary ps-v2-mobile-regenerate-btn"
									disabled={ regenerateMutation.isPending || ! data?.gd_enabled }
									onClick={ handleRegenerate }
									data-figma-node="1271:17989"
								>
									<img src={ iconRegenerate } alt="" width="14" height="15" />
									{ regenerateMutation.isPending
										? 'Regenerating…'
										: 'Regenerate QR code & key' }
								</button>
							</div>
						) }
					</section>
				</div>

				<aside className="ps-v2-mobile-aside" data-figma-node="672:9219">
					<div className="ps-v2-mobile-card ps-v2-mobile-benefits">
						<h3 className="ps-v2-mobile-benefits__title">What you get</h3>
						<p className="ps-v2-mobile-benefits__lead">
							Check email delivery and get failure alerts on your phone.
						</p>
						<ul className="ps-v2-mobile-benefits__list">
							{ ( data?.benefits || [] ).map( ( item ) => (
								<li key={ item }>
									<img src={ iconBenefitCheck } alt="" width="20" height="20" />
									<span>{ item }</span>
								</li>
							) ) }
						</ul>
					</div>
				</aside>
			</div>
		</div>
	);
};

export default MobileAppScreen;
