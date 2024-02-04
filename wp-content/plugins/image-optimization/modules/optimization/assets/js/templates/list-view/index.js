import { __ } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';
import { formatFileSize } from '../../utils';
import { UPGRADE_LINK } from '../../constants';

const notOptimizedTemplate = () => {
	return `
		<button type="button"
					class="button button-primary image-optimization-control__button image-optimization-control__button--optimize">
			${ __( 'Optimize now', 'image-optimizer' ) }
		</button>
	`;
};

const loadingTemplate = ( action ) => {
	let buttonText;

	switch ( action ) {
		case 'restore':
			buttonText = __( 'Restoring…', 'image-optimizer' );
			break;

		case 'optimize':
			buttonText = __( 'Optimizing…', 'image-optimizer' );
			break;

		case 'reoptimize':
			buttonText = __( 'Reoptimizing…', 'image-optimizer' );
			break;

		default:
			buttonText = __( 'Loading…', 'image-optimizer' );
	}

	return `
		<button class="button button-secondary image-optimization-control__button image-optimization-control__button--optimize"
						disabled="">
			<span class="spinner is-active"></span> ${ buttonText }
		</button>
	`;
};

const errorTemplate = ( message, imagesLeft ) => {
	return `
		<span class="image-optimization-control__error-message">${ escapeHTML( message ) }</span>

		${ imagesLeft === 0
		? `<a class="button button-secondary button-large image-optimization-control__button"
				 href="${ UPGRADE_LINK }"
				 target="_blank" rel="noopener noreferrer">
 				${ __( 'Upgrade', 'image-optimizer' ) }
			</a>
		` : `
		<button class="button button-secondary button-large button-link-delete image-optimization-control__button image-optimization-control__button--try-again"
						type="button">
			${ __( 'Try again', 'image-optimizer' ) }
		</button>` }
	`;
};

const optimizedTemplate = ( data ) => {
	const absoluteValue = formatFileSize( data?.saved?.absolute, 1 );

	return `
		<p class="image-optimization-control__property">
			${ __( 'Image sizes optimized', 'image-optimizer' ) }:

			<span>${ data?.sizesOptimized }</span>
		</p>

		<p class="image-optimization-control__property">
			${ data?.saved?.absolute !== 0
		? `${ __( 'Overall saving', 'image-optimizer' ) }: <span>${ data?.saved?.relative }% (${ absoluteValue })</span>`
		: `<span>${ __( 'Image is fully optimized', 'image-optimizer' ) }</span>` }
		</p>

		<div class="image-optimization-control__buttons-wrapper">
			${ data?.canBeRestored ? `
				<button type="button"
					class="button button-secondary image-optimization-control__button image-optimization-control__button--restore-original">
					${ __( 'Restore original', 'image-optimizer' ) }
				</button>
			` : '' }

			<button type="button"
				class="button button-secondary image-optimization-control__button image-optimization-control__button--reoptimize">
				${ __( 'Reoptimize', 'image-optimizer' ) }
			</button>
		</div>
	`;
};

const listViewTemplates = Object.freeze( {
	notOptimizedTemplate,
	loadingTemplate,
	errorTemplate,
	optimizedTemplate,
} );

export default listViewTemplates;
