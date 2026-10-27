import { useState } from 'react';

const EmailLogAccordion = ( { title, defaultOpen, children } ) => {
	const [ open, setOpen ] = useState( defaultOpen );

	return (
		<div className={ `ps-el-accordion${ open ? ' is-open' : '' }` }>
			<button
				type="button"
				className="ps-el-accordion__trigger"
				onClick={ () => setOpen( ! open ) }
				aria-expanded={ open }
			>
				{ title }
			</button>
			{ open && <div className="ps-el-accordion__panel">{ children }</div> }
		</div>
	);
};

export default EmailLogAccordion;
