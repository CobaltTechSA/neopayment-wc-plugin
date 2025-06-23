const path = require('path');
const DependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');

module.exports = {
    mode: process.env.NODE_ENV || 'production',
    entry: {
        'cbo-standard': './assets/js/blocks/cbo-standard.js',
        'cbo-telered': './assets/js/blocks/cbo-telered.js',
    },
    output: {
        path: path.resolve(__dirname, 'build'),
        filename: '[name].js',
    },
    plugins: [ new DependencyExtractionWebpackPlugin() ],
};
