var Encore = require('@symfony/webpack-encore');

Encore
    // the project directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(false)
    .autoProvidejQuery()
    .enableSassLoader()

    .addEntry('chart', './assets/js/chart.min.js')
    .addEntry('chart-resizable', './assets/js/frontend/chart-resizable.js')
    .addEntry('fonts-all', './assets/js/fontawesome.min.js')
    .addEntry('fonts-solid', './assets/js/fa-solid.min.js')
    .addEntry('fonts-regular', './assets/js/fa-regular.min.js')
    .addEntry('board', './assets/js/frontend/board.js')
    .addEntry('dashboard', './assets/js/frontend/dashboard.js')
    .addEntry('issue-share', './assets/js/frontend/issue-share.js')
    .addEntry('typeahead', './assets/js/bootstrap3-typeahead.min.js')
    .addEntry('app-bundle', './assets/js/app.bundle.js')
    .addEntry('bootjs', './assets/js/bootstrap.bundle.min.js')
    .addStyleEntry('app', './assets/css/main.css')
    .addStyleEntry('bootstrap', './assets/css/bootstrap.min.css')

    .addEntry('favicon', './assets/img/favicon.ico')
    .addEntry('google-login', './assets/img/signin_normal.png')

    .createSharedEntry('vendor', [
        './assets/js/bootstrap.bundle.min.js',
        './assets/js/app.bundle.js'
    ])

    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
;

module.exports = Encore.getWebpackConfig();
