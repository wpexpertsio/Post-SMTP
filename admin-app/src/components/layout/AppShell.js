import TopNav from './TopNav';

const AppShell = ( { children, title } ) => (
	<div className="ps-app">
		<TopNav />
		<main className="ps-app__main">
			<div className="ps-page-title">
				<span className="ps-page-title__eyebrow">Post SMTP</span>
				<h1>{ title }</h1>
			</div>
			{ children }
		</main>
	</div>
);

export default AppShell;
