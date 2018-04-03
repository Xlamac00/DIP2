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
    .addEntry('chart-resizable', './assets/js/frontend/chart-resizable.js')
    .addEntry('fonts', './assets/js/fontawesome-all.min.js')
    .addEntry('dashboard', './assets/js/frontend/dashboard.js')
    .addEntry('issue-share', './assets/js/frontend/issue-share.js')
    .addEntry('typeahead', './assets/js/bootstrap3-typeahead.min.js')
    // .addEntry('app-bundle', './assets/js/app.bundle.js')
    .addStyleEntry('app', './assets/css/main.css')
    .addStyleEntry('graph-edit', './assets/css/graph-edit.css')
    .addStyleEntry('bootstrap', './assets/css/bootstrap.min.css')

    .addEntry('favicon', './assets/img/favicon.ico')

    .createSharedEntry('vendor', [
        './assets/js/bootstrap.bundle.min.js',
        './assets/js/app.bundle.js'
    ])

    .enableSassLoader()
    .enableSourceMaps(!Encore.isProduction())
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
;

module.exports = Encore.getWebpackConfig();
