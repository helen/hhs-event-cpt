import { BlockControls } from '@wordpress/block-editor';
import {
	DateTimePicker,
	Popover,
	Toolbar,
	ToolbarButton,
} from '@wordpress/components';
import { date } from '@wordpress/date';
import { useState } from '@wordpress/element';

import UrlPicker from './UrlPicker';

export default (props) => {
	const {
		setAttributes,
		isSelected,
		attributes: {
			endDateOn,
			endDate,
			endTime,
			startDate,
			startTime,
			startTimeOn,
			venue,
			venueUrl
		},
	} = props;

	let formattedStartDate = new Date();

	if ( startDate && startDate.length && startTime && startTime.length ) {
		formattedStartDate = startDate + 'T' + startTime;
	}

	let formattedEndDate = formattedStartDate;

	if ( endDate && endDate.length && endTime && endTime.length ) {
		formattedEndDate = endDate + 'T' + endTime;
	}

	const [startDateTime, setStartDateTime] = useState(formattedStartDate);
	const [endDateTime, setEndDateTime] = useState(formattedEndDate);
	const [startDatePickerOpen, setStartDatePickerOpen] = useState(false);
	const [endDatePickerOpen, setEndDatePickerOpen] = useState(false);

	return (
	<>
		<BlockControls>
			{!endDateOn && (
				<Toolbar>
					<ToolbarButton
						onClick={() => {
							// This looks weird but it's because it's about to flop
							if ( startTimeOn ) {
								setAttributes({startTime: ''});
							}

							setAttributes({startTimeOn: !startTimeOn})
						}}
					>
						{startTimeOn ? 'Hide start time' : 'Show start time'}
					</ToolbarButton>
				</Toolbar>
			)}
			<Toolbar>
				<ToolbarButton
					onClick={() => {
						// This looks weird but it's because it's about to flop
						if ( endDateOn ) {
							setAttributes({endDate: '', endTime: ''});
							setEndDateTime(formattedStartDate);
						}

						setAttributes({endDateOn: !endDateOn})
					}}
				>
					{endDateOn ? 'Remove end date' : 'Use end date'}
				</ToolbarButton>
			</Toolbar>
		</BlockControls>
		<p>
			<span
				className='date-time-picker'
				onClick={() => setStartDatePickerOpen(!startDatePickerOpen)}
			>
				{date( 'F j, Y', startDateTime )}
				{!endDateOn && startTimeOn && (
					<>
					{' '} at {date( 'g:iA', startDateTime )}
					</>
				)}
			</span>

			{startDatePickerOpen && (
				<Popover
					position='bottom left'
					onClose={() => setStartDatePickerOpen(false)}
					className='hhs-event-date-popover'
				>
					<h3>Start Date / Time</h3>
					<DateTimePicker
						currentDate={startDateTime}
						onChange={ ( date ) => {
							setStartDateTime( date );

							const dateTime = date.split('T');
							
							setAttributes({
								startDate: dateTime[0]
							});

							if ( startTimeOn ) {
								setAttributes({
									startTime: dateTime[1]
								});
							} else {
								setAttributes({
									startTime: ''
								});
							}
						} }
						is12Hour={true}
					/>
				</Popover>
			)}

			{endDateOn && (
				<>
					{' '} – {' '}
					<span
						className='date-time-picker'
						onClick={() => setEndDatePickerOpen(!endDatePickerOpen)}
					>
						{date( 'F j, Y', endDateTime )}
					</span>
				</>
			)}

			{endDatePickerOpen && (
				<Popover
					position='bottom left'
					onClose={() => setEndDatePickerOpen(false)}
					className='hhs-event-date-popover'
				>
					<h3>End Date / Time</h3>
					<DateTimePicker
						currentDate={endDateTime}
						onChange={ ( date ) => {
							setEndDateTime( date );

							const dateTime = date.split('T');
							
							setAttributes({
								endDate: dateTime[0],
								endTime: dateTime[1],
							});
						} }
						is12Hour={true}
					/>
				</Popover>
			)}
		
			<UrlPicker
				url={venueUrl}
				onLinkChange={(newUrl) => {
					setAttributes({
						venueUrl: newUrl,
					});
				}}
				linkPlaceholder='Enter Venue URL (optional)'
				onTextChange={(value) => setAttributes({ venue: value })}
				placeholder='Enter venue name here'
				text={venue}
				isBlockSelected={isSelected}
				allowedFormats={[]}
				linkOptional={true}
			/>
		</p>
	</>
	);
}
