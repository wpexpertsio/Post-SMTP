import { useParams } from 'react-router-dom';
import ToolsSubnav from './components/ToolsSubnav';
import SendTestEmailPanel from './components/SendTestEmailPanel';
import CheckConnectionPanel from './components/CheckConnectionPanel';
import DiagnosticReportPanel from './components/DiagnosticReportPanel';
import ImportExportPanel from './components/ImportExportPanel';
import ResetPluginPanel from './components/ResetPluginPanel';
import { DEFAULT_TOOL_SLUG } from './toolsNav';

const ToolsScreen = () => {
	const { toolSlug = DEFAULT_TOOL_SLUG } = useParams();

	const renderPanel = () => {
		switch ( toolSlug ) {
			case 'send-test-email':
				return <SendTestEmailPanel />;
			case 'check-connection':
				return <CheckConnectionPanel />;
			case 'diagnostic-report':
				return <DiagnosticReportPanel />;
			case 'import-export':
				return <ImportExportPanel />;
			case 'reset-plugin':
				return <ResetPluginPanel />;
			default:
				return (
					<div className="ps-v2-tools-placeholder">
						<h2 className="ps-v2-tools-panel__title">Coming soon</h2>
						<p className="ps-v2-tools-panel__lead">
							This tool will be available in an upcoming release.
						</p>
					</div>
				);
		}
	};

	return (
		<div className="ps-v2-tools-page" data-figma-node="547:6890">
			<div className="ps-v2-tools-layout">
				<aside className="ps-v2-tools-layout__nav">
					<ToolsSubnav activeSlug={ toolSlug } />
				</aside>
				<div className="ps-v2-tools-layout__main">{ renderPanel() }</div>
			</div>
		</div>
	);
};

export default ToolsScreen;
