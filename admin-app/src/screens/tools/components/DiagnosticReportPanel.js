import { useEffect, useMemo, useState } from '@wordpress/element';
import FormNotice from '../../connections/components/FormNotice';
import useDiagnosticReport from '../hooks/useDiagnosticReport';
import { TOOLS_NAV_ICONS } from '../toolsNavAssets';

const DiagnosticReportPanel = () => {
	const { bootstrapQuery, runMutation, sendMutation } = useDiagnosticReport();
	const data = bootstrapQuery.data;

	const [ ticket, setTicket ] = useState( '' );
	const [ email, setEmail ] = useState( '' );
	const [ rows, setRows ] = useState( [] );
	const [ plainText, setPlainText ] = useState( '' );
	const [ notice, setNotice ] = useState( null );
	const [ fieldErrors, setFieldErrors ] = useState( {} );

	useEffect( () => {
		if ( data?.defaults?.support_email && ! email ) {
			setEmail( data.defaults.support_email );
		}
	}, [ data?.defaults?.support_email, email ] );

	useEffect( () => {
		if ( data?.report?.rows?.length ) {
			setRows( data.report.rows );
			setPlainText( data.report.plain_text || '' );
		}
	}, [ data?.report ] );

	const generatedLabel = useMemo( () => {
		if ( ! data?.report?.generated_at ) {
			return '';
		}
		const ts = Date.parse( data.report.generated_at );
		if ( Number.isNaN( ts ) ) {
			return '';
		}
		return `Report generated ${ new Date( ts ).toLocaleString() }`;
	}, [ data?.report?.generated_at ] );

	const handleRun = async () => {
		setNotice( null );
		try {
			const result = await runMutation.mutateAsync();
			if ( result?.rows ) {
				setRows( result.rows );
				setPlainText( result.plain_text || '' );
			}
		} catch ( err ) {
			setNotice( {
				type: 'error',
				message: err?.message || 'Could not run the diagnostic report.',
			} );
		}
	};

	const handleCopy = async () => {
		if ( ! plainText ) {
			setNotice( { type: 'error', message: 'Run the report before copying.' } );
			return;
		}
		try {
			await navigator.clipboard.writeText( plainText );
			setNotice( { type: 'success', message: 'Report copied to clipboard.' } );
		} catch ( err ) {
			setNotice( { type: 'error', message: 'Could not copy the report.' } );
		}
	};

	const handleDownload = () => {
		if ( ! plainText ) {
			setNotice( { type: 'error', message: 'Run the report before downloading.' } );
			return;
		}
		const blob = new Blob( [ plainText ], { type: 'text/plain;charset=utf-8' } );
		const url = URL.createObjectURL( blob );
		const link = document.createElement( 'a' );
		link.href = url;
		link.download = 'post-smtp-diagnostic-report.txt';
		link.click();
		URL.revokeObjectURL( url );
	};

	const validateSend = () => {
		const errors = {};
		if ( ! ticket.trim() ) {
			errors.ticket = 'Enter your ticket number or forum username.';
		}
		if ( ! email.trim() || ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email.trim() ) ) {
			errors.email = 'Enter a valid email address.';
		}
		if ( ! rows.length ) {
			errors.report = 'Run the diagnostic report first.';
		}
		setFieldErrors( errors );
		return Object.keys( errors ).length === 0;
	};

	const handleSend = async () => {
		setNotice( null );
		if ( ! validateSend() ) {
			setNotice( { type: 'error', message: 'Fix the highlighted fields before sending.' } );
			return;
		}

		try {
			const result = await sendMutation.mutateAsync( {
				ticket: ticket.trim(),
				email: email.trim(),
			} );
			setNotice( {
				type: result?.success ? 'success' : 'error',
				message:
					result?.message ||
					( result?.success ? data?.copy?.send_success : data?.copy?.send_error ),
			} );
		} catch ( err ) {
			setNotice( {
				type: 'error',
				message: err?.message || data?.copy?.send_error || 'Send failed.',
			} );
		}
	};

	if ( bootstrapQuery.isLoading ) {
		return <p className="ps-v2-tools-loading">Loading…</p>;
	}

	if ( bootstrapQuery.isError ) {
		return <FormNotice type="error" message="Could not load Diagnostic Report." />;
	}

	return (
		<div className="ps-v2-tools-diagnostic" data-figma-node="547:7309">
			<section className="ps-v2-tools-diagnostic__help" data-figma-node="547:7310">
				<header className="ps-v2-tools-panel__hero">
					<div className="ps-v2-tools-panel__hero-icon" aria-hidden="true">
						<img src={ TOOLS_NAV_ICONS[ 'diagnostic-report' ] } alt="" width="24" height="24" />
					</div>
					<div>
						<h2 className="ps-v2-tools-panel__title">Need help?</h2>
						<p className="ps-v2-tools-panel__lead">
							{ data?.copy?.need_help_lead ||
								'Email this report to support instead of posting it publicly.' }
						</p>
					</div>
				</header>

				<div className="ps-v2-tools-form-card ps-v2-tools-diagnostic__send-card" data-figma-node="547:7321">
					<div className="ps-v2-form-field">
						<div className="ps-v2-form-field__head">
							<span className="ps-v2-form-field__label">Ticket number / Forum username</span>
							<p className="ps-v2-form-field__hint">Helps support find your message.</p>
						</div>
						<input
							type="text"
							className="ps-v2-field-input"
							value={ ticket }
							onChange={ ( e ) => setTicket( e.target.value ) }
							placeholder="Your ticket or forum username"
							aria-invalid={ fieldErrors.ticket ? 'true' : undefined }
						/>
						{ fieldErrors.ticket && (
							<p className="ps-v2-field-error">{ fieldErrors.ticket }</p>
						) }
					</div>

					<div className="ps-v2-form-field">
						<div className="ps-v2-form-field__head">
							<span className="ps-v2-form-field__label">Send report to</span>
							<p className="ps-v2-form-field__hint">Defaults to Post SMTP support.</p>
						</div>
						<input
							type="email"
							className="ps-v2-field-input"
							value={ email }
							onChange={ ( e ) => setEmail( e.target.value ) }
							aria-invalid={ fieldErrors.email ? 'true' : undefined }
						/>
						{ fieldErrors.email && (
							<p className="ps-v2-field-error">{ fieldErrors.email }</p>
						) }
					</div>

					{ fieldErrors.report && (
						<p className="ps-v2-field-error">{ fieldErrors.report }</p>
					) }

					<button
						type="button"
						className="ps-v2-btn ps-v2-tools-diagnostic__send-btn"
						onClick={ handleSend }
						disabled={ sendMutation.isPending }
					>
						{ sendMutation.isPending ? 'Sending…' : 'Send Diagnostic Report' }
					</button>
				</div>
			</section>

			<section className="ps-v2-tools-diagnostic__report" data-figma-node="547:7442">
				<header className="ps-v2-tools-panel__hero">
					<div className="ps-v2-tools-panel__hero-icon" aria-hidden="true">
						<img src={ TOOLS_NAV_ICONS[ 'diagnostic-report' ] } alt="" width="24" height="24" />
					</div>
					<div>
						<h2 className="ps-v2-tools-panel__title">Diagnostic report</h2>
						<p className="ps-v2-tools-panel__lead">Details about your site for support.</p>
					</div>
				</header>

				<FormNotice
					type={ notice?.type }
					message={ notice?.message }
					onDismiss={ () => setNotice( null ) }
				/>

				<div
					className="ps-v2-tools-form-card ps-v2-tools-diagnostic__report-card"
					data-figma-node="547:7453"
				>
					<div className="ps-v2-tools-diagnostic__actions" data-figma-node="547:7499">
						<button
							type="button"
							className="ps-v2-btn ps-v2-btn--primary"
							onClick={ handleRun }
							disabled={ runMutation.isPending }
						>
							{ runMutation.isPending ? 'Running…' : 'Run again' }
						</button>
						<button
							type="button"
							className="ps-v2-btn ps-v2-tools-diagnostic__outline-btn"
							onClick={ handleCopy }
							disabled={ ! plainText }
						>
							Copy report
						</button>
						<button
							type="button"
							className="ps-v2-btn ps-v2-tools-diagnostic__outline-btn"
							onClick={ handleDownload }
							disabled={ ! plainText }
						>
							Download
						</button>
					</div>

					{ generatedLabel && (
						<p className="ps-v2-tools-diagnostic__generated">{ generatedLabel }</p>
					) }

					{ ! rows.length && ! runMutation.isPending && (
						<p className="ps-v2-tools-diagnostic__empty">
							{ data?.copy?.empty_report ||
								'Run the diagnostic report to collect site details.' }
						</p>
					) }

					{ rows.length > 0 && (
						<div className="ps-v2-tools-diagnostic__table-wrap" data-figma-node="547:7508">
							<table className="ps-v2-tools-diagnostic__table">
								<thead>
									<tr>
										<th scope="col">Property</th>
										<th scope="col">Value</th>
									</tr>
								</thead>
								<tbody>
									{ rows.map( ( row ) => (
										<tr key={ row.label }>
											<th scope="row">{ row.label }</th>
											<td>{ row.value }</td>
										</tr>
									) ) }
								</tbody>
							</table>
						</div>
					) }
				</div>
			</section>
		</div>
	);
};

export default DiagnosticReportPanel;
