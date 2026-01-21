const path = require('path');

module.exports = {
    plugins: [
        require('autoprefixer')
    ],
    browserslist: [
        ">0.2%",
        "not dead",
        "not op_mini all"
    ],
    configureWebpack: {
        output: {
            crossOriginLoading: 'anonymous'
        }
    },
    module: {
        loaders: [
            {
                test: /\.modernizrrc.js$/,
                loader: "modernizr"
            },
            {
                test: /\.modernizrrc(\.json)?$/,
                loader: "modernizr!json"
            }
        ]
    },
    resolve: {
        alias: {
            modernizr$: path.resolve(__dirname, "build/modernizr/.modernizrrc")
        }
    }
}