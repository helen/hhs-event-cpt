/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import {
  KeyboardShortcuts,
  Popover,
  Button,
  Tooltip,
} from '@wordpress/components';
import {
  PlainText,
  URLInput,
} from '@wordpress/block-editor';
import { rawShortcut } from '@wordpress/keycodes';
import { debounce } from 'lodash';

export default function UrlPicker({
  url,
  opensInNewTab,
  onLinkChange,
  onTextChange,
  placeholder,
  text,
  linkPlaceholder,
  isBlockSelected,
  linkOptional,
  ...extraProps
}) {
  const [isURLPickerOpen, setIsURLPickerOpen] = useState(false);
  const [selected, setSelected] = useState(false);
  const openLinkControl = () => {
	setIsURLPickerOpen(true);
	return false; // prevents default behaviour for event
  };

  const hasLink = url && url.length && 'has-link';

  const linkControl = isURLPickerOpen && (
	<Popover
	  position="bottom left"
	  onClose={() => {
		setIsURLPickerOpen(false);
		setSelected(false);
	  }}
	>
	  <URLInput
		className="wp-block-navigation-link__inline-link-input"
		value={url}
		onChange={onLinkChange}
		disableSuggestions={true}
		placeholder={linkPlaceholder}
	  />
	</Popover>
  );
  return (
	<div className={`hhs-url-picker ${hasLink}`}>
	  {selected && (
		<KeyboardShortcuts
		  bindGlobal
		  shortcuts={{
			[rawShortcut.primary('k')]: openLinkControl,
		  }}
		/>
	  )}
	  {linkControl}
	  <PlainText
		value={text}
		onChange={(value) => {
		  onTextChange(value.replace(/[\r\n\t]+/gm, ' '));
		}}
		placeholder={placeholder}
		keepPlaceholderOnFocus
		onFocus={debounce(() => {
		  setSelected(true);
		}, 100)}
		onBlur={debounce(() => {
		  setSelected(false);
		}, 100)}
		{...extraProps}
	  />

	  <Button
		className={`
		link-toggle is-small
		${selected && 'active'}
		${isBlockSelected || 'hidden'}
		`}
		icon={<span className="dashicons dashicons-admin-links"></span>}
		onClick={openLinkControl}
	  />

	  {!linkOptional && text && !url && (
		<Tooltip text='This item is missing a link'>
		  <span
			tabIndex={0}
			className="missing-link dashicons dashicons-warning"
		  />
		</Tooltip>
	  )}
	</div>
  );
}
