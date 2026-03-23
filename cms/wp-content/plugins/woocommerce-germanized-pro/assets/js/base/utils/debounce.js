export const debounce = (
    func,
    wait,
    immediate
) => {
    let timeout;
    let latestArgs= null;

    const debounced = ( ( ...args ) => {
        latestArgs = args;
        if ( timeout ) {
            clearTimeout( timeout );
        }
        timeout = setTimeout( () => {
            timeout = null;
            if ( ! immediate && latestArgs ) {
                func( ...latestArgs );
            }
        }, wait );
        if ( immediate && ! timeout ) {
            func( ...args );
        }
    } );

    // Clear the debounce queue and execute any pending function immediately.
    debounced.flush = () => {
        if ( timeout && latestArgs ) {
            func( ...latestArgs );
            clearTimeout( timeout );
            timeout = null;
        }
    };

    // Clear the debounce queue without executing any functions.
    debounced.clear = () => {
        if ( timeout ) {
            clearTimeout( timeout );
        }
        timeout = null;
    };

    return debounced;
};