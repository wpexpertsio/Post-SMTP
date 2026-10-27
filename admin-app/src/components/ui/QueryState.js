const QueryState = ( { isLoading, isError, error, children, skeleton } ) => {
	if ( isLoading ) {
		return skeleton || <p className="ps-query-state">Loading…</p>;
	}
	if ( isError ) {
		return (
			<p className="ps-query-state ps-query-state--error" role="alert">
				{ error?.message || 'Something went wrong. Please refresh and try again.' }
			</p>
		);
	}
	return children;
};

export default QueryState;
