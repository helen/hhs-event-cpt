import { registerBlockType } from '@wordpress/blocks';

// Split the Edit component out.
import Edit from './edit';

import './index.scss';

/**
 * Register example block
 */
export default registerBlockType(
	'hhs/event-info',
	{
		title: 'Event Information',
		description: 'Basic event information',
		category: 'common',
		icon: 'info-outline',
		supports: {
			multiple: false,
			html: false,
		},
		attributes: {
			endDateOn: {
				type: 'boolean',
				default: false,
			},
			endDate: {
				type: 'string',
				source: 'meta',
				meta: 'end_date',
			},
			endTime: {
				type: 'string',
				source: 'meta',
				meta: 'end_time',
			},
			startDate: {
				type: 'string',
				source: 'meta',
				meta: 'start_date',
			},
			startTime: {
				type: 'string',
				source: 'meta',
				meta: 'start_time',
			},
			startTimeOn: {
				type: 'boolean',
				default: true,
			},
			venue: {
				type: 'string',
				source: 'meta',
				meta: 'venue',
			},
			venueUrl: {
				type: 'string',
				source: 'meta',
				meta: 'venue_url',
			},
		},
		transforms: {},
		variations: [],
		edit: Edit,
		save: () => null,
	},
);
