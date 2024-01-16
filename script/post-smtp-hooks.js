var Hook = {
    hooks: [],

    register: function ( name, callback ) {
        if( 'undefined' == typeof( Hook.hooks[name] ) )
            Hook.hooks[name] = []
        Hook.hooks[name].push( callback )
    },

    call: function ( name, arguments ) {
        if( 'undefined' != typeof( Hook.hooks[name] ) )
            for( i = 0; i < Hook.hooks[name].length; ++i )
                if( true != Hook.hooks[name][i]( arguments ) ) { break; }
    }
}
