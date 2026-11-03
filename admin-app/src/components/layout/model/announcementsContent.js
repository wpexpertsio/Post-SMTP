import { getAdminConfig, getAdminUrls } from '../../../config/admin';

const SAMPLE_BODY = (
	<>
		<p>
			<strong>Post SMTP v2.0.0</strong> adds compatibility with WordPress 7.1 and updates its
			internal libraries to the latest patched releases for improved security and stability.
		</p>
		<p>
			<strong>Here are the key updates in this release:</strong>
		</p>
		<p>
			WordPress 7.1 Compatibility: Added full compatibility with WordPress 7.1, so you can update
			to the latest version of WordPress and keep sending your emails without any issues.
		</p>
		<p>
			Security and Stability: Updated internal libraries to their latest patched releases,
			strengthening security and improving overall stability.
		</p>
		<p>
			<strong>Check the changelog below to see what&apos;s new in Post SMTP v2.0.0.</strong>
		</p>
		<p>
			<strong>Improvement</strong>
		</p>
		<ul>
			<li>
				WordPress 7.1 Compatibility – Added compatibility with WordPress 7.1 for a smooth
				experience on the latest version of WordPress.
			</li>
			<li>
				Security and Stability – Updated internal libraries to patched releases for improved
				security and stability.
			</li>
		</ul>
		<p>
			Update to Post SMTP v2.0.0 for full WordPress 7.1 compatibility and stronger security and
			stability.
		</p>
	</>
);

export function getWhatsNewEntries() {
	const config = getAdminConfig();
	const fromConfig = config.whatsNew;
	if ( Array.isArray( fromConfig ) && fromConfig.length ) {
		return fromConfig;
	}

	const urls = getAdminUrls();
	return [
		{
			id: 'sample-1',
			dateLabel: '4 weeks ago',
			title: 'Post SMTP v2.0.0 – WordPress 7.1 Compatibility and Security Updates',
			body: SAMPLE_BODY,
			readMoreUrl: urls.docs,
			readMoreText: 'Post SMTP',
		},
		{
			id: 'sample-2',
			dateLabel: '4 weeks ago',
			title: 'Post SMTP v2.0.0 – WordPress 7.1 Compatibility and Security Updates',
			body: SAMPLE_BODY,
			readMoreUrl: urls.docs,
			readMoreText: 'Post SMTP',
		},
	];
}
