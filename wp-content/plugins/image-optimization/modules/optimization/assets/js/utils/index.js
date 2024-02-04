import { __, sprintf } from '@wordpress/i18n';

export const formatFileSize = ( fileSizeInBytes, decimals = 2 ) => {
	const sizes = [
		// translators: %s: file size in bytes
		__( '%s Bytes', 'image-optimizer' ),
		// translators: %s: file size in kilobytes
		__( '%s Kb', 'image-optimizer' ),
		// translators: %s: file size in megabytes
		__( '%s Mb', 'image-optimizer' ),
		// translators: %s: file size in gigabytes
		__( '%s Gb', 'image-optimizer' ),
	];

	if ( ! fileSizeInBytes ) {
		// translators: %s: file size in bytes
		return sprintf( __( '%s Bytes', 'image-optimizer' ), 0 );
	}

	const currentScale = Math.floor( Math.log( fileSizeInBytes ) / Math.log( 1024 ) );
	const formattedValue = parseFloat( ( fileSizeInBytes / Math.pow( 1024, currentScale ) ).toFixed( decimals ) );

	// eslint-disable-next-line @wordpress/valid-sprintf
	return sprintf( sizes[ currentScale ], formattedValue );
};
