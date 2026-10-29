import { Link } from 'react-router-dom';
import { getAdminConfig, getAdminUrls } from '../../config/admin';
import iconChevron from '../../assets/help-docs/icon-chevron.svg';
import iconCommunity from '../../assets/help-docs/icon-community.svg';
import iconContactSupport from '../../assets/help-docs/icon-contact-support.svg';
import iconDocumentation from '../../assets/help-docs/icon-documentation.svg';
import iconGoogle from '../../assets/help-docs/icon-google.svg';
import iconNeedHand from '../../assets/help-docs/icon-need-hand.svg';
import iconSetupSpark from '../../assets/help-docs/icon-setup-spark.svg';
import iconUpgradeArrow from '../../assets/help-docs/icon-upgrade-arrow.svg';
import {
	EXPERT_SESSION,
	GUIDES,
	QUICK_SUPPORT_LINKS,
} from './model/helpDocsContent';

const QUICK_ICONS = {
	documentation: iconDocumentation,
	community: iconCommunity,
	contact: iconContactSupport,
};

const QuickSupportCard = ( { link } ) => {
	const icon = QUICK_ICONS[ link.icon ];
	const className = 'ps-v2-help-quick-card';

	const inner = (
		<>
			<span className="ps-v2-help-quick-card__icon" aria-hidden="true">
				<img src={ icon } alt="" width="17" height="20" />
			</span>
			<span className="ps-v2-help-quick-card__copy">
				<span className="ps-v2-help-quick-card__title">{ link.title }</span>
				<span className="ps-v2-help-quick-card__desc">{ link.description }</span>
			</span>
		</>
	);

	if ( link.external ) {
		return (
			<a
				href={ link.url }
				className={ className }
				target="_blank"
				rel="noreferrer"
			>
				{ inner }
			</a>
		);
	}

	return (
		<Link to={ link.url } className={ className }>
			{ inner }
		</Link>
	);
};

const GuideRow = ( { guide, isLast } ) => (
	<a
		href={ guide.url }
		className={ `ps-v2-help-guide-row${ isLast ? ' ps-v2-help-guide-row--last' : '' }` }
		target="_blank"
		rel="noreferrer"
	>
		<span className="ps-v2-help-guide-row__copy">
			<span className="ps-v2-help-guide-row__title">{ guide.title }</span>
			<span className="ps-v2-help-guide-row__desc">{ guide.description }</span>
		</span>
		<span className="ps-v2-help-guide-row__meta">
			<span className="ps-v2-help-pill">{ guide.badge }</span>
			<img
				className="ps-v2-help-guide-row__chevron"
				src={ iconChevron }
				alt=""
				width="7"
				height="12"
				aria-hidden="true"
			/>
		</span>
	</a>
);

const HelpDocsScreen = () => {
	const urls = getAdminUrls();
	const { isPro } = getAdminConfig();

	return (
		<div className="ps-v2-help-page" data-figma-node="672:8978">
			<div className="ps-v2-help-layout" data-figma-node="672:9007">
				<div className="ps-v2-help-main">
					<section
						className="ps-v2-help-card ps-v2-help-card--quick"
						data-figma-node="672:9009"
					>
						<h2 className="ps-v2-help-section-title">Quick Support Links</h2>
						<div className="ps-v2-help-quick-grid" data-figma-node="672:9012">
							{ QUICK_SUPPORT_LINKS.map( ( link ) => (
								<QuickSupportCard key={ link.id } link={ link } />
							) ) }
						</div>
					</section>

					<section
						className="ps-v2-help-card ps-v2-help-card--guides"
						data-figma-node="672:9045"
					>
						<div className="ps-v2-help-guides-head" data-figma-node="672:9048">
							<h2 className="ps-v2-help-section-title">Guides</h2>
							<span className="ps-v2-help-pill ps-v2-help-pill--subtle">
								Short walkthroughs
							</span>
						</div>

						<div className="ps-v2-help-guide-list" data-figma-node="672:9052">
							{ GUIDES.map( ( guide, index ) => (
								<GuideRow
									key={ guide.id }
									guide={ guide }
									isLast={ index === GUIDES.length - 1 }
								/>
							) ) }
						</div>

						<p className="ps-v2-help-footnote" data-figma-node="672:9101">
							Still stuck? Send a diagnostic report from{ ' ' }
							<Link to="/tools/diagnostic-report">
								Troubleshooting → Diagnostic report
							</Link>
							.
						</p>
					</section>
				</div>

				<aside className="ps-v2-help-aside" data-figma-node="672:9102">
					<section
						className="ps-v2-help-card ps-v2-help-gmail-promo"
						data-figma-node="672:9103"
					>
						<div className="ps-v2-help-gmail-promo__head" data-figma-node="672:9106">
							<span className="ps-v2-help-gmail-promo__logo" aria-hidden="true">
								<img src={ iconGoogle } alt="" width="22" height="22" />
							</span>
							<span className="ps-v2-help-gmail-promo__tag">
								<img
									src={ iconSetupSpark }
									alt=""
									width="9"
									height="12"
									aria-hidden="true"
								/>
								20-second setup
							</span>
						</div>
						<div className="ps-v2-help-gmail-promo__copy">
							<strong>Connect Gmail in one click</strong>
							<p>Sign in with Google and Post SMTP does the rest.</p>
						</div>
						{ isPro ? (
							<Link to="/connections" className="ps-v2-help-btn ps-v2-help-btn--pro">
								Add Gmail connection
								<img src={ iconUpgradeArrow } alt="" width="14" height="10" />
							</Link>
						) : (
							<a
								href={ urls.pro }
								className="ps-v2-help-btn ps-v2-help-btn--pro"
								target="_blank"
								rel="noreferrer"
							>
								Upgrade to Pro
								<img src={ iconUpgradeArrow } alt="" width="14" height="10" />
							</a>
						) }
					</section>

					<section
						className="ps-v2-help-card ps-v2-help-expert"
						data-figma-node="672:9132"
					>
						<div className="ps-v2-help-expert__tag" data-figma-node="672:9133">
							<img src={ iconNeedHand } alt="" width="22" height="22" aria-hidden="true" />
							<span>Need a hand?</span>
						</div>
						<div className="ps-v2-help-expert__copy">
							<strong>{ EXPERT_SESSION.title }</strong>
							<p>{ EXPERT_SESSION.body }</p>
						</div>
						<a
							href={ EXPERT_SESSION.url }
							className="ps-v2-help-btn ps-v2-help-btn--primary"
							target="_blank"
							rel="noreferrer"
						>
							{ EXPERT_SESSION.cta }
						</a>
					</section>
				</aside>
			</div>
		</div>
	);
};

export default HelpDocsScreen;
