import { registerBlockType } from '@wordpress/blocks';
import { SVG } from '@wordpress/components';
import { Edit } from './edit';
import metadata from './block.json';

registerBlockType(metadata, {
	edit: Edit
});