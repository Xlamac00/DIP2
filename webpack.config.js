var Encore = require('@symfony/webpack-encore');

Encore
    // the project directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .autoProvidejQuery()

    // uncomment to define the assets of the project
    .addEntry('chart', './assets/js/chart.min.js')
    .addEntry('chart-resizable', './assets/js/chart-resizable.js')
    .addEntry('jquery', './assets/js/jquery.js')
    .addEntry('fonts', './assets/js/fontawesome-all.min.js')
    .addStyleEntry('app', './assets/css/main.css')
    .addStyleEntry('graph-edit', './assets/css/graph-edit.css')
    .addStyleEntry('bootstrap', './assets/css/bootstrap.min.css')

    .addEntry('favicon', './assets/img/favicon.ico')

    .createSharedEntry('vendor', [
        './assets/js/jquery.js',
        './assets/js/bootstrap.bundle.min.js'
    ])

    .enableSassLoader()
    .enableSourceMaps(!Encore.isProduction())
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
;

module.exports = Encore.getWebpackConfig();
