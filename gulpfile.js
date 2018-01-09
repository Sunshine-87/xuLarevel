const elixir = require('laravel-elixir');
var path= require("path");
require('laravel-elixir-vue-2');

elixir(mix => {
  mix.webpack('main.js');
});

Elixir.webpack.mergeConfig({
    resolveLoader: {
        root: path.join(__dirname, 'node_modules'),
    },
    module: {
        loaders: [
            {
                test: /\.css$/,
                loader: 'style-loader!css-loader'
            }]
    },
});