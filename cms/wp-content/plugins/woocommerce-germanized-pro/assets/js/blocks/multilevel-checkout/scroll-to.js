export const maybeScrollToTop = ( scrollPoint ) => {
    if ( ! scrollPoint ) {
        return;
    }

    const yPos = scrollPoint.getBoundingClientRect().bottom;
    const isScrollPointVisible = yPos >= 0 && yPos <= window.innerHeight;

    if ( ! isScrollPointVisible ) {
        scrollPoint.scrollIntoView();
    }
};

export const moveFocusToElement = (
    scrollPoint,
    focusableSelector
) => {
    const focusableElements = scrollPoint.parentElement?.querySelectorAll( focusableSelector ) || [];

    if ( focusableElements.length ) {
        const targetElement = focusableElements[ 0 ];
        maybeScrollToTop( targetElement );
        targetElement?.focus();
    } else {
        maybeScrollToTop( scrollPoint );
    }
};

export const scrollToHTMLElement = (
    ref,
    options
) => {
    const { focusableSelector } = options || {};

    if ( ! window || ! Number.isFinite( window.innerHeight ) ) {
        return;
    }

    const { ownerDocument } = ref.current;
    const { defaultView } = ownerDocument;

    const scrollPoint = defaultView.document.getElementsByClassName( 'with-scroll-to-top__scroll-point' )[0];

    if ( focusableSelector ) {
        moveFocusToElement( scrollPoint, focusableSelector );
    } else {
        maybeScrollToTop( scrollPoint );
    }
};