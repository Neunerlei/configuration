# Configuration - a different approach

![Create new Release](https://github.com/Neunerlei/configuration/workflows/Create%20new%20Release/badge.svg?event=push)


This package provides you a highly opinionated configuration / plugin loading framework for PHP. It provides
built in auto-completion support for your IDE, is compatible with major PSR definitions (PSR-4, PSR-11, PSR-14 and PSR-16),
extendable and ditches the needs to write yaml, ini or json files.

Every configuration you write is done as a PHP class, everything you can configure is provided by a configurator class,
and the resulting configuration state can be cached (if a cache instance is provided) to run your code without loosing performance.

This configuration framework is designed for extendable/pluggable projects, where other developers need to configure your code,
or provide their own configuration in their plugins. Therefore, it does not make much sense if you use this library in a small
codebase you write for yourself. If you, however, are working on a piece of software that should be extendable your
users / clients / customers will love the clean API this way of configuring will provide; even if it might look a bit odd at first glance.

## Documentation
The documentation can be found [here](https://configuration.neunerlei.eu/).

## Running tests

- Clone the repository
- Install the dependencies with ```composer install```
- Run the tests with ```composer test```

## Building the documentation
The documentation is powered by [vuepress](https://vuepress.vuejs.org/), you can quite simply spin up a dev server like so:

- Clone the repository
- Navigate to ```docs```
- Install the dependencies with ```npm install```
- Run the dev server with ```npm run dev```

## Special Thanks
Special thanks goes to the folks at [LABOR.digital](https://labor.digital/) (which is the german word for laboratory and not the english "work" :D) for making it possible to publish my code online.

## Postcardware
You're free to use this package, but if it makes it to your production environment I highly appreciate you sending me a postcard from your hometown, mentioning which of our package(s) you are using.

You can find my address [here](https://www.neunerlei.eu/).

Thank you :D
