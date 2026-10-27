import { useState, useMemo } from '@wordpress/element';
import MailerCard from './MailerCard';
import iconChevronMore from '../../../assets/dashboard/connections/icon-chevron-more.svg';
import iconChevronFewer from '../../../assets/dashboard/connections/icon-chevron-fewer.svg';
import { buildMailerGrid } from '../model/mailerGridLayout';
import { mailerConnectionIcon } from '../model/mailerConnectionIcons';

const MailerGrid = ( { items, selectedSlug, onSelect } ) => {
	const [ expanded, setExpanded ] = useState( false );

	const { rows, moreItems, hiddenMore } = useMemo(
		() => buildMailerGrid( items, expanded ),
		[ items, expanded ]
	);
	const previewHidden = hiddenMore.slice( 0, 3 );
	const hasMoreMailers = moreItems.length > 0;

	return (
		<div className="ps-v2-mailer-choose" data-figma-node="672:5758">
			<div className="ps-v2-mailer-choose__rows">
				{ rows.map( ( row, rowIndex ) => (
					<div key={ rowIndex } className="ps-v2-mailer-choose__row">
						{ row.map( ( item ) => (
							<MailerCard
								key={ item.slug }
								item={ item }
								selected={ selectedSlug === item.slug }
								onSelect={ onSelect }
							/>
						) ) }
					</div>
				) ) }
			</div>

			{ hasMoreMailers && (
				<button
					type="button"
					className={ `ps-v2-mailer-choose__more${ expanded ? ' is-expanded' : '' }` }
					onClick={ () => setExpanded( ! expanded ) }
					aria-expanded={ expanded }
					data-figma-node={ expanded ? '1171:15709' : '672:5888' }
				>
					<span className="ps-v2-mailer-choose__more-left">
						<img
							src={ expanded ? iconChevronFewer : iconChevronMore }
							alt=""
							width="13"
							height="8"
							className="ps-v2-mailer-choose__more-chevron"
						/>
						{ expanded ? 'Show fewer' : 'Show more mailers' }
					</span>
					{ ! expanded && (
						<span className="ps-v2-mailer-choose__more-stack" aria-hidden="true">
							{ previewHidden.map( ( item, i ) => {
								const url = mailerConnectionIcon( item.slug );
								return (
									<span key={ item.slug } className="ps-v2-mailer-choose__more-avatar" style={ { zIndex: 3 - i } }>
										{ url ? <img src={ url } alt="" width="15" height="15" /> : null }
									</span>
								);
							} ) }
							{ hiddenMore.length > previewHidden.length && (
								<span className="ps-v2-mailer-choose__more-count">
									{ hiddenMore.length > 10 ? '10+' : `${ hiddenMore.length }+` }
								</span>
							) }
						</span>
					) }
				</button>
			) }
		</div>
	);
};

export default MailerGrid;
