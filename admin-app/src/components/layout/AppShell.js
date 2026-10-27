import TopNav from './TopNav';

const AppShell = ( { children, title, showEyebrow = true, hideTitle = false, mainClassName = '' } ) => (
	<div className="ps-app">
		<TopNav />
		<main
			className={ `ps-app__main${ hideTitle ? ' ps-app__main--flush' : '' }${ mainClassName ? ` ${ mainClassName }` : '' }` }
		>
			{ ! hideTitle && (
				<header className="ps-page-title">
					{ showEyebrow && <span className="ps-page-title__eyebrow">Post SMTP</span> }
					<h1>{ title }</h1>
				</header>
			) }
			{ children }
		</main>
	</div>
);

export default AppShell;
