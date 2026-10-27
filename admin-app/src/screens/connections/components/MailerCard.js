import { providerIcon } from '../../../components/ui/Icons';
import { mailerConnectionIcon } from '../model/mailerConnectionIcons';
import MailerProTag from './MailerProTag';

const MailerCard = ( { item, selected, onSelect } ) => {
	const iconUrl = mailerConnectionIcon( item.slug ) || ( item.icon ? providerIcon( item.icon ) : null );
	const locked = ! item.configurable;
	/** PRO crown on grid (Gmail excluded via catalog `requires_pro`). */
	const showProTag = Boolean( item.requires_pro );

	return (
		<button
			type="button"
			className={ `ps-v2-mailer-pick${ selected ? ' is-selected' : '' }${ locked && ! showProTag ? ' is-locked' : '' }` }
			onClick={ () => ! locked && onSelect( item.slug ) }
			disabled={ locked }
			aria-pressed={ selected }
		>
			<div className="ps-v2-mailer-pick__main">
				<div className="ps-v2-mailer-pick__icon-wrap">
					{ iconUrl ? (
						<img
							src={ iconUrl }
							alt=""
							className="ps-v2-mailer-pick__icon"
							width="28"
							height="28"
							decoding="async"
							loading="lazy"
						/>
					) : (
						<span className="ps-v2-mailer-pick__initial">{ item.label?.charAt( 0 ) || '?' }</span>
					) }
				</div>
				<span className="ps-v2-mailer-pick__label">{ item.label }</span>
			</div>
			{ showProTag ? (
				<MailerProTag />
			) : (
				<span className={ `ps-v2-mailer-pick__radio${ selected ? ' is-checked' : '' }` } aria-hidden="true" />
			) }
		</button>
	);
};

export default MailerCard;
