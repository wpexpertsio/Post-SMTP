import { useEffect, useMemo, useState } from '@wordpress/element';
import { Link } from 'react-router-dom';
import FormNotice from '../../connections/components/FormNotice';
import useSendTestEmail from '../hooks/useSendTestEmail';
import { TOOLS_NAV_ICONS } from '../toolsNavAssets';
import ToolsConnectionSelect from './ToolsConnectionSelect';

const SendTestEmailPanel = () => {
	const { bootstrapQuery, sendMutation, previewMutation } = useSendTestEmail();
	const data = bootstrapQuery.data;

	const connections = data?.connections || [];
	const defaultConnectionId = data?.default_connection_id ?? 0;
	const hasUsable = Boolean( data?.has_usable_connection );

	const [ connectionId, setConnectionId ] = useState( defaultConnectionId );
	const [ recipient, setRecipient ] = useState( '' );
	const [ subject, setSubject ] = useState( '' );
	const [ message, setMessage ] = useState( '' );
	const [ notice, setNotice ] = useState( null );
	const [ fieldErrors, setFieldErrors ] = useState( {} );
	const [ previewHtml, setPreviewHtml ] = useState( null );

	useEffect( () => {
		if ( data?.defaults?.subject && ! subject ) {
			setSubject( data.defaults.subject );
		}
	}, [ data?.defaults?.subject, subject ] );

	useEffect( () => {
		if ( connections.length && connections.some( ( c ) => c.id === defaultConnectionId ) ) {
			setConnectionId( defaultConnectionId );
		} else if ( connections.length ) {
			setConnectionId( connections[0].id );
		}
	}, [ connections, defaultConnectionId ] );

	const selectedConnection = useMemo(
		() => connections.find( ( c ) => c.id === connectionId ),
		[ connections, connectionId ]
	);

	const validate = () => {
		const errors = {};
		if ( ! selectedConnection?.is_usable ) {
			errors.connection = 'Select a configured connection.';
		}
		if ( ! recipient.trim() || ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( recipient.trim() ) ) {
			errors.recipient = 'Enter a valid recipient email address.';
		}
		if ( ! subject.trim() ) {
			errors.subject = 'Subject cannot be empty.';
		}
		setFieldErrors( errors );
		return Object.keys( errors ).length === 0;
	};

	const handleSend = async () => {
		setNotice( null );
		if ( ! validate() ) {
			setNotice( { type: 'error', message: 'Fix the highlighted fields before sending.' } );
			return;
		}

		try {
			const result = await sendMutation.mutateAsync( {
				connection_id: connectionId,
				recipient: recipient.trim(),
				subject: subject.trim(),
				message,
			} );
			setNotice( {
				type: result?.success ? 'success' : 'error',
				message: result?.message || ( result?.success ? data?.copy?.success : 'Test failed.' ),
			} );
		} catch ( err ) {
			setNotice( {
				type: 'error',
				message: err?.message || 'The test email could not be sent.',
			} );
		}
	};

	const handlePreview = async () => {
		setNotice( null );
		setPreviewHtml( null );
		try {
			const result = await previewMutation.mutateAsync( {
				subject: subject.trim() || data?.defaults?.subject,
				message,
			} );
			if ( result?.preview?.html ) {
				setPreviewHtml( result.preview.html );
			}
		} catch ( err ) {
			setNotice( {
				type: 'error',
				message: err?.message || 'Could not build preview.',
			} );
		}
	};

	if ( bootstrapQuery.isLoading ) {
		return <p className="ps-v2-tools-loading">Loading…</p>;
	}

	if ( bootstrapQuery.isError ) {
		return <FormNotice type="error" message="Could not load Send Test Email settings." />;
	}

	if ( ! hasUsable || connections.length === 0 ) {
		return (
			<div className="ps-v2-tools-empty" data-figma-node="532:6404">
				<h2 className="ps-v2-tools-panel__title">Send test email</h2>
				<p className="ps-v2-tools-panel__lead">
					Configure a mailer connection before you can send a test email.
				</p>
				<Link to="/connections" className="ps-v2-btn ps-v2-btn--primary">
					Open Connections
				</Link>
			</div>
		);
	}

	return (
		<div className="ps-v2-tools-send" data-figma-node="532:6404">
			<header className="ps-v2-tools-panel__hero" data-figma-node="532:6406">
				<div className="ps-v2-tools-panel__hero-icon" aria-hidden="true">
					<img src={ TOOLS_NAV_ICONS[ 'send-test-email' ] } alt="" width="24" height="24" />
				</div>
				<div>
					<h2 className="ps-v2-tools-panel__title">Send test email</h2>
					<p className="ps-v2-tools-panel__lead">Check that your site can send email.</p>
				</div>
			</header>

			<FormNotice
				type={ notice?.type }
				message={ notice?.message }
				onDismiss={ () => setNotice( null ) }
			/>

			<div className="ps-v2-tools-form-card">
				<div className="ps-v2-form-field">
					<div className="ps-v2-form-field__head">
						<span className="ps-v2-form-field__label">Connection</span>
						<p className="ps-v2-form-field__hint">Choose which connection to use for this test email.</p>
					</div>
					<ToolsConnectionSelect
						connections={ connections }
						value={ connectionId }
						onChange={ setConnectionId }
						selectedConnection={ selectedConnection }
						invalid={ Boolean( fieldErrors.connection ) }
					/>
					{ fieldErrors.connection && (
						<p className="ps-v2-field-error">{ fieldErrors.connection }</p>
					) }
				</div>

				<div className="ps-v2-form-field">
					<div className="ps-v2-form-field__head">
						<span className="ps-v2-form-field__label">Send to</span>
						<p className="ps-v2-form-field__hint">Use an inbox you can open now.</p>
					</div>
					<input
						type="email"
						className="ps-v2-field-input"
						value={ recipient }
						onChange={ ( e ) => setRecipient( e.target.value ) }
						placeholder="name@example.com"
						aria-invalid={ fieldErrors.recipient ? 'true' : undefined }
					/>
					{ fieldErrors.recipient && (
						<p className="ps-v2-field-error">{ fieldErrors.recipient }</p>
					) }
				</div>

				<div className="ps-v2-form-field">
					<div className="ps-v2-form-field__head">
						<span className="ps-v2-form-field__label">Subject</span>
						<p className="ps-v2-form-field__hint">Shown in the recipient&apos;s inbox.</p>
					</div>
					<input
						type="text"
						className="ps-v2-field-input"
						value={ subject }
						onChange={ ( e ) => setSubject( e.target.value ) }
						aria-invalid={ fieldErrors.subject ? 'true' : undefined }
					/>
					{ fieldErrors.subject && (
						<p className="ps-v2-field-error">{ fieldErrors.subject }</p>
					) }
				</div>

				<div className="ps-v2-form-field">
					<div className="ps-v2-form-field__head">
						<span className="ps-v2-form-field__label">Message (optional)</span>
						<p className="ps-v2-form-field__hint">Leave blank for our standard text.</p>
					</div>
					<textarea
						className="ps-v2-field-input ps-v2-tools-textarea"
						rows={ 4 }
						value={ message }
						onChange={ ( e ) => setMessage( e.target.value ) }
						placeholder="Optional custom message"
					/>
				</div>
			</div>

			<div className="ps-v2-tools-actions">
				<button
					type="button"
					className="ps-v2-btn ps-v2-btn--primary"
					disabled={ sendMutation.isPending || ! selectedConnection?.is_usable }
					onClick={ handleSend }
					data-figma-node="547:6953"
				>
					{ sendMutation.isPending ? 'Sending…' : 'Send test email' }
				</button>
				<button
					type="button"
					className="ps-v2-btn ps-v2-btn--ghost ps-v2-tools-preview-btn"
					disabled={ previewMutation.isPending }
					onClick={ handlePreview }
				>
					{ previewMutation.isPending ? 'Loading preview…' : 'Preview' }
				</button>
			</div>

			{ previewHtml && (
				<div className="ps-v2-tools-preview" role="dialog" aria-label="Email preview">
					<div className="ps-v2-tools-preview__head">
						<h3>Email preview</h3>
						<button type="button" className="ps-v2-tools-preview__close" onClick={ () => setPreviewHtml( null ) }>
							Close
						</button>
					</div>
					<iframe
						title="Test email preview"
						className="ps-v2-tools-preview__frame"
						srcDoc={ previewHtml }
						sandbox=""
					/>
				</div>
			) }
		</div>
	);
};

export default SendTestEmailPanel;
