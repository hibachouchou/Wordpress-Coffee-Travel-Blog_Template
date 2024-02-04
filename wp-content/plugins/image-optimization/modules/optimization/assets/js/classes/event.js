class OptimizationEvent {
	static dispatchSingleImageOptimizedEvent() {
		const event = new Event( 'image-optimizer/optimize/single' );
		document.dispatchEvent( event );
	}
}

export default OptimizationEvent;
