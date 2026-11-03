import { useEffect } from 'react';
import { createPortal } from 'react-dom';
import iconDrawerClose from '../../assets/brand/icon-drawer-close.svg';
import { getWhatsNewEntries } from './model/announcementsContent';

const AnnouncementsDrawer = ( { open, onClose } ) => {
	useEffect( () => {
		if ( ! open ) {
			return undefined;
		}

		const onKeyDown = ( event ) => {
			if ( event.key === 'Escape' ) {
				onClose();
			}
		};

		const previousOverflow = document.body.style.overflow;
		document.body.style.overflow = 'hidden';
		document.addEventListener( 'keydown', onKeyDown );

		return () => {
			document.body.style.overflow = previousOverflow;
			document.removeEventListener( 'keydown', onKeyDown );
		};
	}, [ open, onClose ] );

	if ( ! open ) {
		return null;
	}

	const entries = getWhatsNewEntries();

	return createPortal(
		<div
			className="ps-v2-whats-new-backdrop"
			role="presentation"
			onClick={ onClose }
		>
			<aside
				className="ps-v2-whats-new-drawer"
				data-figma-node="985:16316"
				role="dialog"
				aria-modal="true"
				aria-label="What's New"
				onClick={ ( event ) => event.stopPropagation() }
			>
				<header className="ps-v2-whats-new-drawer__head">
					<h2 className="ps-v2-whats-new-drawer__title">What&apos;s New?</h2>
					<button
						type="button"
						className="ps-v2-whats-new-drawer__close"
						onClick={ onClose }
						aria-label="Close"
					>
						<img src={ iconDrawerClose } alt="" width="13" height="13" />
					</button>
				</header>
				<div className="ps-v2-whats-new-drawer__scroll">
					{ entries.map( ( entry ) => (
						<article key={ entry.id } className="ps-v2-whats-new-entry">
							<p className="ps-v2-whats-new-entry__date">{ entry.dateLabel }</p>
							<h3 className="ps-v2-whats-new-entry__title">{ entry.title }</h3>
							<div className="ps-v2-whats-new-entry__body">{ entry.body }</div>
							{ entry.readMoreUrl && (
								<p className="ps-v2-whats-new-entry__more">
									Read more at{ ' ' }
									<a
										href={ entry.readMoreUrl }
										target="_blank"
										rel="noreferrer"
									>
										{ entry.readMoreText || 'Post SMTP' }
									</a>
								</p>
							) }
						</article>
					) ) }
				</div>
			</aside>
		</div>,
		document.body
	);
};

export default AnnouncementsDrawer;
