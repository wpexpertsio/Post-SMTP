import { getAdminUrls } from '../../../config/admin';
import FormGenerator from '../../../components/form-generator/FormGenerator';
import { mailerConnectionIcon } from '../model/mailerConnectionIcons';
import useMailerCatalog from '../hooks/useMailerCatalog';

const OAUTH_PROVIDERS = {
	gmail_api: 'gmail_api',
	office365_api: 'office365_api',
	zohomail_api: 'zohomail_api',
};

const SENDGRID_LAYOUT = [
	{ type: 'fields', keys: [ 'connection_title' ] },
	{ type: 'divider' },
	{ type: 'group', keys: [ 'from_name', 'prevent_sender_name_override' ] },
	{ type: 'group', keys: [ 'from_email', 'prevent_sender_email_override' ] },
	{ type: 'divider' },
	{ type: 'api_section', keys: [ 'sendgrid_api_key' ] },
];

const ConfigureMailerForm = ( { schema, values, onChange, provider } ) => {
	const urls = getAdminUrls();
	const catalogQuery = useMailerCatalog();
	const catalogItem = catalogQuery.data?.items?.find( ( item ) => item.slug === provider );
	const oauthSlug = OAUTH_PROVIDERS[ provider ];
	const oauthUrl = oauthSlug ? `${ urls.wizard }&socket=${ oauthSlug }&step=2` : null;

	const displayName = schema?.display_name || catalogItem?.label || 'Mailer';
	const configureTitle = schema?.configure_title || `Configure ${ displayName }`;
	const configureSubtitle = schema?.description || '';
	const iconUrl = mailerConnectionIcon( provider );
	const useSendGridLayout = provider === 'sendgrid_api';

	const renderField = ( key ) => {
		const field = schema?.fields?.[ key ];
		if ( ! field ) {
			return null;
		}
		return (
			<FormGenerator
				key={ key }
				schema={ { fields: { [ key ]: field }, field_sequence: [ key ] } }
				values={ values }
				onChange={ onChange }
			/>
		);
	};

	const renderApiSection = ( keys ) => {
		const section = schema?.sections?.api_credentials;
		return (
			<div className="ps-v2-configure-form__api">
				<div className="ps-v2-configure-form__api-head">
					<div>
						<h4 className="ps-v2-configure-form__section-title">{ section?.title || 'API credentials' }</h4>
						{ section?.description && (
							<p className="ps-v2-configure-form__section-hint">{ section.description }</p>
						) }
					</div>
					{ schema?.doc_url && (
						<a href={ schema.doc_url } className="ps-v2-configure-form__doc-link" target="_blank" rel="noreferrer">
							{ schema.doc_label || 'View documentation' }
						</a>
					) }
				</div>
				{ keys.map( ( key ) => renderField( key ) ) }
			</div>
		);
	};

	return (
		<div className="ps-v2-configure-form" data-figma-node="672:5588">
			<header className="ps-v2-configure-form__hero">
				<div className="ps-v2-configure-form__hero-icon" aria-hidden="true">
					{ iconUrl ? <img src={ iconUrl } alt="" width="30" height="28" /> : null }
				</div>
				<div>
					<h3 className="ps-v2-configure-form__hero-title">{ configureTitle }</h3>
					{ configureSubtitle && (
						<p className="ps-v2-configure-form__hero-sub">{ configureSubtitle }</p>
					) }
				</div>
			</header>

			{ oauthUrl && (
				<p className="ps-v2-configure-form__oauth">
					<a className="ps-v2-btn ps-v2-btn--primary" href={ oauthUrl }>
						Authorize with provider
					</a>
					<span className="ps-v2-configure-form__oauth-hint">
						Complete OAuth in the wizard, then return here to save.
					</span>
				</p>
			) }

			{ useSendGridLayout ? (
				<div className="ps-v2-configure-form__sections">
					{ SENDGRID_LAYOUT.map( ( block, index ) => {
						if ( block.type === 'divider' ) {
							return <hr key={ `div-${ index }` } className="ps-v2-configure-form__divider" />;
						}
						if ( block.type === 'api_section' ) {
							return <div key={ `api-${ index }` }>{ renderApiSection( block.keys ) }</div>;
						}
						if ( block.type === 'group' ) {
							return (
								<div key={ `grp-${ index }` } className="ps-v2-configure-form__group">
									{ block.keys.map( ( key ) => renderField( key ) ) }
								</div>
							);
						}
						return (
							<div key={ `fld-${ index }` }>
								{ block.keys.map( ( key ) => renderField( key ) ) }
							</div>
						);
					} ) }
				</div>
			) : (
				<FormGenerator schema={ schema } values={ values } onChange={ onChange } />
			) }
		</div>
	);
};

export default ConfigureMailerForm;
