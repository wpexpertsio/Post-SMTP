import { useEffect } from '@wordpress/element';

const ImageLightbox = ( { src, alt, onClose } ) => {
	useEffect( () => {
		const onKey = ( e ) => {
			if ( e.key === 'Escape' ) {
				onClose();
			}
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ onClose ] );

	if ( ! src ) {
		return null;
	}

	return (
		<div className="ps-v2-image-lightbox" role="dialog" aria-modal="true" aria-label={ alt || 'Preview' }>
			<button type="button" className="ps-v2-image-lightbox__backdrop" aria-label="Close" onClick={ onClose } />
			<div className="ps-v2-image-lightbox__panel">
				<button type="button" className="ps-v2-image-lightbox__close" onClick={ onClose }>
					Close
				</button>
				<img src={ src } alt={ alt || '' } className="ps-v2-image-lightbox__img" />
			</div>
		</div>
	);
};

export default ImageLightbox;
