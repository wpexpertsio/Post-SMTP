import { useState } from '@wordpress/element';
import iconUpgradeArrow from '../../../assets/help-docs/icon-upgrade-arrow.svg';
import iconMagnify from '../../../assets/settings/icon-preview-magnify.svg';
import ImageLightbox from './ImageLightbox';

const ProFeaturePreviewCard = ( { title, description, imageSrc, upgradeUrl } ) => {
	const [ lightbox, setLightbox ] = useState( false );

	return (
		<article className="ps-v2-settings-queue-preview-card">
			<div className="ps-v2-settings-queue-preview-card__media">
				<button
					type="button"
					className="ps-v2-settings-queue-preview-card__shot-wrap"
					onClick={ () => setLightbox( true ) }
					aria-label={ `Enlarge ${ title } preview` }
				>
					<img src={ imageSrc } alt="" className="ps-v2-settings-queue-preview-card__shot" />
					<span className="ps-v2-settings-queue-preview-card__magnify" aria-hidden="true">
						<img src={ iconMagnify } alt="" width="20" height="20" />
					</span>
				</button>
			</div>
			<div className="ps-v2-settings-queue-preview-card__body">
				<h3 className="ps-v2-settings-queue-preview-card__title">{ title }</h3>
				<p className="ps-v2-settings-queue-preview-card__desc">{ description }</p>
				<a
					href={ upgradeUrl }
					className="ps-v2-reports-cta ps-v2-settings-queue-preview-card__cta"
					target="_blank"
					rel="noreferrer"
				>
					Upgrade to Pro
					<img src={ iconUpgradeArrow } alt="" width="14" height="10" />
				</a>
			</div>
			{ lightbox && (
				<ImageLightbox src={ imageSrc } alt={ title } onClose={ () => setLightbox( false ) } />
			) }
		</article>
	);
};

export default ProFeaturePreviewCard;
