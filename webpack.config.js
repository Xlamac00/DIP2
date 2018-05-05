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
    .addEntry('tips', './assets/js/frontend/tips.js')
    .addEntry('board', './assets/js/frontend/board.js')
    .addEntry('issue', './assets/js/frontend/issue.js')
    .addEntry('dashboard', './assets/js/frontend/dashboard.js')
    .addEntry('sharing', './assets/js/frontend/entity-sharing.js')
    .addEntry('typeahead', './assets/js/bootstrap3-typeahead.min.js')
    .addEntry('app-bundle', './assets/js/app.bundle.js')
    .addEntry('bootjs', './assets/js/bootstrap.bundle.min.js')
    .addEntry('datepickerjs', './assets/js/bootstrap-datepicker.min.js')
    .addStyleEntry('datepicker', './assets/css/bootstrap-datepicker3.css')
    .addStyleEntry('app', './assets/css/main.css')
    .addStyleEntry('bootstrap', './assets/css/bootstrap.min.css')
    .addStyleEntry('tips-style', './assets/css/tips.css')

    .addEntry('favicon', './assets/img/graph.ico')
    .addEntry('google-login', './assets/img/signin_normal.png')
    .addEntry('arrow-left', './assets/img/arrow_left.png')
    .addEntry('arrow-right', './assets/img/arrow_right.png')
    .addEntry('arrow-top', './assets/img/arrow_top.png')
    .addEntry('arrow-bottom', './assets/img/arrow_bottom.png')
    .addEntry('arrow-top-right', './assets/img/arrow_top_right.png')
    .addEntry('slide1a', './assets/img/slide_1a.jpeg')
    .addEntry('slide1b', './assets/img/slide_1b.gif')
    .addEntry('slide2a', './assets/img/slide_2a.jpeg')
    .addEntry('slide2b', './assets/img/slide_2b.gif')
    .addEntry('slide3', './assets/img/slide_3.jpeg')

    .createSharedEntry('vendor', [
        './assets/js/bootstrap.bundle.min.js',
        './assets/js/app.bundle.js'
    ])

    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
;

module.exports = Encore.getWebpackConfig();
