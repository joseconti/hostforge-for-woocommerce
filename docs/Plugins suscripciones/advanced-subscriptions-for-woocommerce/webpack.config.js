const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = [
    {
        ...defaultConfig,
        entry: {
            'cart-line-items': './src/cart-line-items.js',
        },
        output: {
            ...defaultConfig.output,
            path: path.resolve( __dirname, 'wc-block' ),
            filename: '[name].js',
        },
    },
];