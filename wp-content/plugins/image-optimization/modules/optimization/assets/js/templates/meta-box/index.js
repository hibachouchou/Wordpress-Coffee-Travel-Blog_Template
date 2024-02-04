import { __ } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';
import { formatFileSize } from '../../utils';
import { UPGRADE_LINK } from '../../constants';

const notOptimizedTemplate = () => {
	return `
		<p class="image-optimization-control__property">
			${ __( 'Status', 'image-optimizer' ) }:

			<span class="image-optimization-control__property-value">
				${ __( 'Not optimized', 'image-optimizer' ) }
			</span>
		</p>

		<div class="image-optimization-control__action-button-wrapper">
			<button type="button"
							class="button button-primary image-optimization-control__button image-optimization-control__button--optimize">
				${ __( 'Optimize now', 'image-optimizer' ) }
			</button>
		</div>
	`;
};

const loadingTemplate = () => {
	return `
		<p class="image-optimization-control__property">
			${ __( 'Status', 'image-optimizer' ) }:

			<span class="image-optimization-control__property-value">
				${ __( 'In Progress', 'image-optimizer' ) }
			</span>
		</p>

		<div class="image-optimization-control__action-spinner-wrapper">
			<span class="spinner is-active"></span>
		</div>
	`;
};

const errorTemplate = ( message, imagesLeft ) => {
	return `
		<p class="image-optimization-control__property">
			${ __( 'Status', 'image-optimizer' ) }:

			<span class="image-optimization-control__property-value">
				${ __( 'Error', 'image-optimizer' ) }
			</span>
		</p>

		<p class="image-optimization-control__property">
			${ __( 'Reason', 'image-optimizer' ) }:

			<span class="image-optimization-control__property-value">
				${ escapeHTML( message ) }
			</span>
		</p>

		<div class="image-optimization-control__action-button-wrapper">
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
		</div>
	`;
};

const optimizedTemplate = ( data ) => {
	const absoluteValue = formatFileSize( data?.saved?.absolute, 1 );

	return `
		<p class="image-optimization-control__property">
			${ __( 'Status', 'image-optimizer' ) }:

			<span class="image-optimization-control__property-value">
				${ __( 'Optimized', 'image-optimizer' ) }
			</span>
		</p>

		<p class="image-optimization-control__property">
			${ __( 'Image sizes optimized', 'image-optimizer' ) }:

			<span class="image-optimization-control__property-value">
				${ data?.sizesOptimized }
			</span>
		</p>

		<p class="image-optimization-control__property">
			${ data?.saved?.absolute !== 0
		? `
				<span class="image-optimization-control__property-value">
					${ __( 'Overall saving', 'image-optimizer' ) }: ${ data?.saved?.relative }% (${ absoluteValue })
				</span>
			` : `
				<span class="image-optimization-control__property-value">
					${ __( 'Image is fully optimized', 'image-optimizer' ) }
				</span>
			` }
		</p>

		<div class="image-optimization-control__action-button-wrapper">
			${ data?.canBeRestored ? `
				<button class="button button-link image-optimization-control__button image-optimization-control__button--restore-original"
								type="button">
					${ __( 'Restore original', 'image-optimizer' ) }
				</button>
			` : '' }

			<button class="button button-link image-optimization-control__button image-optimization-control__button--reoptimize"
							type="button">
				${ __( 'Reoptimize', 'image-optimizer' ) }
			</button>
		</div>
	`;
};

const metaBoxTemplates = Object.freeze( {
	notOptimizedTemplate,
	loadingTemplate,
	errorTemplate,
	optimizedTemplate,
} );

export default metaBoxTemplates;
