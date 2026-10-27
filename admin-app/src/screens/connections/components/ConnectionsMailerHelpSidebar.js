import { mailerSidebarHelp } from '../model/mailerSidebarHelp';
import {
	iconExternalLink,
	iconNeedHand,
	iconPostSmtpBrand,
	iconSetupHelperTag,
} from './connectionHelperAssets';

const HelpCard = ( {
	tag,
	tagIcon,
	tagIconFull = false,
	brandMark = null,
	title,
	body,
	cta,
	url,
	figmaNode,
	variant = 'link',
} ) => (
	<aside className="ps-v2-conn-helper-card" data-figma-node={ figmaNode }>
		<div className="ps-v2-conn-helper-card__tag">
			<span
				className={
					tagIconFull
						? 'ps-v2-conn-helper-card__tag-icon ps-v2-conn-helper-card__tag-icon--full'
						: 'ps-v2-conn-helper-card__tag-icon'
				}
				aria-hidden="true"
			>
				<img src={ tagIcon } alt="" width={ tagIconFull ? 22 : 13 } height={ tagIconFull ? 22 : 14 } />
			</span>
			<span className="ps-v2-conn-helper-card__tag-label">{ tag }</span>
		</div>
		<div
			className={
				brandMark
					? 'ps-v2-conn-helper-card__content ps-v2-conn-helper-card__content--with-brand'
					: 'ps-v2-conn-helper-card__content'
			}
		>
			<div className="ps-v2-conn-helper-card__main">
				<div className="ps-v2-conn-helper-card__copy">
					<strong className="ps-v2-conn-helper-card__title">{ title }</strong>
					<p>{ body }</p>
				</div>
				{ variant === 'button' ? (
					<a
						href={ url }
						className="ps-v2-conn-helper-card__cta"
						target="_blank"
						rel="noreferrer"
					>
						{ cta }
					</a>
				) : (
					<a
						href={ url }
						className="ps-v2-conn-helper-card__doc-link"
						target="_blank"
						rel="noreferrer"
					>
						<span>{ cta }</span>
						<img src={ iconExternalLink } alt="" width="13" height="13" />
					</a>
				) }
			</div>
			{ brandMark && (
				<div className="ps-v2-conn-helper-card__brand" aria-hidden="true">
					<img src={ brandMark } alt="" width="88" height="88" />
				</div>
			) }
		</div>
	</aside>
);

const ConnectionsMailerHelpSidebar = ( { providerSlug } ) => {
	const help = mailerSidebarHelp( providerSlug );
	if ( ! help ) {
		return null;
	}

	return (
		<div className="ps-v2-conn-helper-stack">
			<HelpCard
				{ ...help.setup }
				tagIcon={ iconSetupHelperTag }
				brandMark={ iconPostSmtpBrand }
				figmaNode="672:5689"
			/>
			<HelpCard
				{ ...help.expert }
				tagIcon={ iconNeedHand }
				tagIconFull
				figmaNode="672:5704"
				variant="button"
			/>
		</div>
	);
};

export default ConnectionsMailerHelpSidebar;
