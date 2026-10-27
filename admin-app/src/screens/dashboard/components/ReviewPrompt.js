import Button from '../../../components/ui/Button';
import { getAdminUrls } from '../../../config/admin';

const ReviewPrompt = () => {
	const urls = getAdminUrls();

	return (
		<section className="ps-review-card">
			<div className="ps-stars" aria-hidden="true">★★★★★</div>
			<p>Loving Post SMTP? Leave a quick review — it helps us grow.</p>
			<Button variant="secondary" href={ urls.review } target="_blank" rel="noreferrer">
				Leave a review ↗
			</Button>
		</section>
	);
};

export default ReviewPrompt;
