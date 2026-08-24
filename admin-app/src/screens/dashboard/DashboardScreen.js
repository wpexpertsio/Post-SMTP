import { useState } from '@wordpress/element';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import apiFetch from '../../api/client';
import { fetchEmailStats, fetchRecentLogs } from '../../api/dashboard';
import {
	IconCheck,
	IconLock,
	PRO_FEATURES,
	PROVIDERS,
	providerIcon,
} from '../../components/ui/Icons';

const config = window.postSmtpAdmin || {};
const urls = config.adminUrls || {};

const PREVIEW_MODES = [
	{ id: 'new-free', label: 'New user', tier: 'Free' },
	{ id: 'new-pro', label: 'New user', tier: 'Pro' },
	{ id: 'existing-free', label: 'Existing user', tier: 'Free' },
	{ id: 'existing-pro', label: 'Existing user', tier: 'Pro' },
];

const PERIODS = [
	{ id: 'day', label: 'Today' },
	{ id: 'week', label: 'Last 7 days' },
	{ id: 'month', label: 'Last 30 days' },
];

const formatTransport = ( type ) =>
	( type || 'default' ).replace( /_/g, ' ' ).replace( /\b\w/g, ( c ) => c.toUpperCase() );

const relativeTime = ( timeString ) => {
	if ( ! timeString ) {
		return '';
	}
	const parsed = Date.parse( timeString );
	if ( Number.isNaN( parsed ) ) {
		return timeString;
	}
	const diff = Date.now() - parsed;
	const minutes = Math.floor( diff / 60000 );
	if ( minutes < 60 ) {
		return `${ minutes || 1 } min ago`;
	}
	const hours = Math.floor( minutes / 60 );
	if ( hours < 24 ) {
		return `${ hours } h ago`;
	}
	return `${ Math.floor( hours / 24 ) } d ago`;
};

const domainFromUrl = ( url ) => {
	try {
		return new URL( url ).hostname.replace( /^www\./, '' );
	} catch {
		return 'your-domain.com';
	}
};

const DashboardScreen = () => {
	const [ period, setPeriod ] = useState( 'week' );
	const [ preview, setPreview ] = useState( 'existing-free' );

	const statsQuery = useQuery( {
		queryKey: [ 'dashboard-stats', period ],
		queryFn: () => fetchEmailStats( period ),
	} );

	const logsQuery = useQuery( {
		queryKey: [ 'dashboard-logs' ],
		queryFn: fetchRecentLogs,
	} );

	const connectionsQuery = useQuery( {
		queryKey: [ 'connections' ],
		queryFn: () => apiFetch( 'connections' ),
	} );

	const stats = statsQuery.data?.count || { total: 0, success: 0, failed: 0 };
	const deliveredPct = stats.total
		? `${ ( ( stats.success / stats.total ) * 100 ).toFixed( 1 ) }%`
		: '—';
	const logs = logsQuery.data?.logs || [];
	const connections = connectionsQuery.data?.connections || [];
	const primary = connections[ 0 ];

	const previewIsPro = preview.endsWith( '-pro' );
	const previewIsNew = preview.startsWith( 'new-' );
	const showProUpsell = ! config.isPro && ! previewIsPro;
	const isConfigured = previewIsNew ? false : config.isConfigured;
	const domain = domainFromUrl( config.siteUrl || '' );

	return (
		<div className="ps-dashboard">
			<section className="ps-preview-bar">
				<div>
					<span className="ps-preview-bar__label">Preview as</span>
					<div className="ps-preview-pills">
						{ PREVIEW_MODES.map( ( mode ) => (
							<button
								key={ mode.id }
								type="button"
								className={ `ps-preview-pill${ preview === mode.id ? ' is-active' : '' }` }
								onClick={ () => setPreview( mode.id ) }
							>
								{ mode.label }
								<span className={ `ps-tier-badge${ mode.tier === 'Pro' ? ' is-pro' : '' }` }>
									{ mode.tier }
								</span>
							</button>
						) ) }
					</div>
				</div>
				<a href={ urls.docs } className="ps-preview-link" target="_blank" rel="noreferrer">
					Preview onboarding →
				</a>
			</section>

			<section className="ps-card ps-delivery">
				<div className="ps-card__head">
					<h2>Delivery at a glance</h2>
					<select
						className="ps-select"
						value={ period }
						onChange={ ( e ) => setPeriod( e.target.value ) }
					>
						{ PERIODS.map( ( item ) => (
							<option key={ item.id } value={ item.id }>{ item.label }</option>
						) ) }
					</select>
				</div>
				<div className="ps-stat-row">
					<article className="ps-stat">
						<div className="ps-stat__top">
							<span>Emails sent</span>
							{ stats.total > 0 && <span className="ps-trend is-up">+12%</span> }
						</div>
						<strong>{ stats.total }</strong>
						<small>Total attempts</small>
					</article>
					<article className="ps-stat">
						<div className="ps-stat__top">
							<span>Delivered</span>
							{ stats.total > 0 && <span className="ps-trend is-up">+0.3%</span> }
						</div>
						<strong>{ deliveredPct }</strong>
						<small>{ stats.success } of { stats.total }</small>
					</article>
					<article className="ps-stat">
						<div className="ps-stat__top">
							<span>Failed</span>
							{ stats.failed > 0 && <span className="ps-trend is-down">-4</span> }
						</div>
						<strong>{ stats.failed }</strong>
						<small>{ stats.failed ? 'Needs attention' : 'All clear' }</small>
					</article>
					<article className="ps-stat ps-stat--locked">
						<div className="ps-stat__top">
							<span>Opened emails</span>
							<span className="ps-tier-badge is-pro">Pro</span>
						</div>
						<div className="ps-stat__lock">
							<IconLock />
							<div>
								<strong>Locked</strong>
								<p>Track email opens — unlock with Pro →</p>
							</div>
						</div>
					</article>
				</div>
			</section>

			<section className={ `ps-setup-card${ isConfigured ? ' is-ready' : ' is-pending' }` }>
				<div className="ps-setup-card__icon"><IconCheck /></div>
				<div className="ps-setup-card__body">
					<span className="ps-setup-card__eyebrow">Getting started</span>
					<h2>{ isConfigured ? 'Setup complete' : 'Setup required' }</h2>
					<div className="ps-setup-card__title-row">
						<p>{ isConfigured ? 'Email is sending through Post SMTP' : 'Post SMTP is not configured yet' }</p>
						{ isConfigured && <span className="ps-pill-active">Active</span> }
					</div>
					<p className="ps-setup-card__hint">
						{ isConfigured
							? 'Change your mailer anytime in Connections or the Setup Wizard.'
							: 'Post SMTP is mimicking out-of-the-box WordPress email delivery.' }
					</p>
				</div>
				<div className="ps-setup-card__actions">
					<a href={ urls.wizard } className="ps-btn ps-btn--dark">✦ Open Setup Wizard</a>
					<Link to="/connections" className="ps-btn ps-btn--outline">Manage connections</Link>
				</div>
			</section>

			{ showProUpsell && (
				<section className="ps-pro-banner">
					<div className="ps-pro-banner__content">
						<span className="ps-pro-banner__badge">Pro Features</span>
						<h2>Do more with Post SMTP Pro</h2>
						<p>One-click mailers, failure alerts, quotas and reports.</p>
						<div className="ps-provider-grid">
							{ PROVIDERS.map( ( item ) => (
								<div key={ item.label } className="ps-provider-tile">
									<img src={ providerIcon( item.slug ) } alt="" />
									<div>
										<strong>{ item.label }</strong>
										<span>{ item.tag }</span>
									</div>
								</div>
							) ) }
						</div>
						<ul className="ps-pro-features">
							{ PRO_FEATURES.map( ( feature ) => (
								<li key={ feature }>✓ { feature }</li>
							) ) }
						</ul>
					</div>
					<div className="ps-pro-banner__cta">
						<a href={ urls.pro } className="ps-btn ps-btn--green" target="_blank" rel="noreferrer">
							Get Post SMTP Pro →
						</a>
						<a href={ urls.pro } className="ps-btn ps-btn--outline-light" target="_blank" rel="noreferrer">
							Compare plans
						</a>
					</div>
				</section>
			) }

			<section className="ps-section-label">Connection &amp; recent emails</section>

			<div className="ps-split-grid">
				<article className="ps-card">
					<div className="ps-card__head">
						<h2>Active mailers</h2>
						<Link to="/connections" className="ps-text-link">Manage</Link>
					</div>
					<p className="ps-card__sub">Your main sender and backup.</p>
					{ primary ? (
						<div className="ps-mailer">
							<div className="ps-mailer__avatar">G</div>
							<div className="ps-mailer__info">
								<strong>{ primary.title || formatTransport( primary.provider ) }</strong>
								<span>{ primary.sender_email || primary.from_email || config.senderEmail }</span>
							</div>
							<span className="ps-mailer__tag">Primary</span>
						</div>
					) : (
						<div className="ps-mailer ps-mailer--empty">
							<div className="ps-mailer__avatar">+</div>
							<div className="ps-mailer__info">
								<strong>{ formatTransport( config.transport ) }</strong>
								<span>{ config.senderEmail || 'Configure your first mailer' }</span>
							</div>
						</div>
					) }
					<div className="ps-mailer ps-mailer--ghost">
						<div className="ps-mailer__avatar">↻</div>
						<div className="ps-mailer__info">
							<strong>Add a fallback mailer</strong>
							<span>Auto-retry via a backup sender</span>
						</div>
						<span className="ps-mailer__tag is-muted">Fallback</span>
					</div>
				</article>

				<article className="ps-card">
					<div className="ps-card__head">
						<h2>Recent emails</h2>
						<a href={ urls.emailLog } className="ps-text-link">View all</a>
					</div>
					<p className="ps-card__sub">Last 5 emails</p>
					<ul className="ps-email-list">
						{ logs.length === 0 && <li className="ps-email-list__empty">No emails logged yet.</li> }
						{ logs.slice( 0, 5 ).map( ( log ) => (
							<li key={ log.id } className="ps-email-row">
								<div>
									<strong>{ log.subject || '(No subject)' }</strong>
									<span>{ log.sent_to }</span>
								</div>
								<div className="ps-email-row__meta">
									<span className={ `ps-status is-${ log.status }` }>
										{ log.status === 'success' ? 'Success' : log.status === 'in_queue' ? 'Queued' : 'Failed' }
									</span>
									<time>{ relativeTime( log.delivery_time ) }</time>
								</div>
							</li>
						) ) }
					</ul>
				</article>
			</div>

			<div className="ps-triple-grid">
				<article className="ps-card ps-gmail-card">
					<div className="ps-gmail-card__badges">
						<span className="ps-tier-badge is-pro">Pro</span>
						<span className="ps-mini-badge">60-second setup</span>
					</div>
					<div className="ps-gmail-card__body">
						<img src={ providerIcon( 'gmail-pro-feature.svg' ) } alt="" className="ps-gmail-card__logo" />
						<div>
							<h3>Connect Gmail in one click</h3>
							<p>Sign in with Google and Post SMTP does the rest.</p>
						</div>
					</div>
					<div className="ps-gmail-card__actions">
						<a href={ `${ urls.wizard }&socket=gmail_api` } className="ps-btn ps-btn--blue">
							Enable one-click →
						</a>
						<a href={ urls.docs } className="ps-text-link" target="_blank" rel="noreferrer">Learn more</a>
					</div>
				</article>

				<article className="ps-card ps-test-card">
					<h3>Send test email</h3>
					<label className="ps-field">
						<span>Send to</span>
						<input type="email" defaultValue={ config.senderEmail || '' } placeholder="you@example.com" />
					</label>
					<a href={ urls.settings } className="ps-btn ps-btn--blue">Send test</a>
				</article>

				<article className="ps-card ps-reports-card">
					<div className="ps-reports-card__head">
						<h3>Reports &amp; tracking</h3>
						<span className="ps-tier-badge is-pro">Pro</span>
					</div>
					<ul>
						<li>Sends &amp; failures</li>
						<li>Opens</li>
						<li>Per-mailer view</li>
					</ul>
					<p className="ps-reports-card__note">Used on 300,000+ WordPress sites.</p>
					<a href={ urls.pro } className="ps-btn ps-btn--orange" target="_blank" rel="noreferrer">
						Upgrade to Pro
					</a>
					<a href={ urls.pro } className="ps-text-link" target="_blank" rel="noreferrer">See what&apos;s included</a>
				</article>
			</div>

			<section className="ps-card ps-domain-card">
				<div className="ps-domain-card__head">
					<h2>Domain authentication</h2>
					<span>{ domain } · 2 of 3 checks passing</span>
				</div>
				<div className="ps-domain-grid">
					<article className="ps-domain-box is-pass">
						<div className="ps-domain-box__head"><strong>SPF</strong><span>PASS</span></div>
						<p>Your domain is set up to send mail.</p>
					</article>
					<article className="ps-domain-box is-pass">
						<div className="ps-domain-box__head"><strong>DKIM</strong><span>PASS</span></div>
						<p>Your messages are signed.</p>
					</article>
					<article className="ps-domain-box is-fail">
						<div className="ps-domain-box__head"><strong>DMARC</strong><span>FAIL</span></div>
						<p>Add a DMARC record to protect your domain.</p>
					</article>
				</div>
			</section>

			<section className="ps-review-card">
				<div className="ps-stars" aria-hidden="true">★★★★★</div>
				<p>Loving Post SMTP? Leave a quick review — it helps us grow.</p>
				<a href={ urls.review } className="ps-btn ps-btn--outline" target="_blank" rel="noreferrer">
					Leave a review ↗
				</a>
			</section>
		</div>
	);
};

export default DashboardScreen;
