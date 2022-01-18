# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## [2.7.0](https://github.com/Neunerlei/configuration/compare/v2.6.1...v2.7.0) (2022-01-18)


### Features

* **ConfigState:** allow options when merging config states into each other ([2baf171](https://github.com/Neunerlei/configuration/commit/2baf1717d0fc35cf7b0adc2f452fc2a2b40d1887))

### [2.6.1](https://github.com/Neunerlei/configuration/compare/v2.6.0...v2.6.1) (2021-11-24)


### Bug Fixes

* **ConfigState:** don't execute callback of handleWatchers() twice ([6b307ee](https://github.com/Neunerlei/configuration/commit/6b307eef856d7855a83c8bdca8a76748a3efc7ff))

## [2.6.0](https://github.com/Neunerlei/configuration/compare/v2.5.1...v2.6.0) (2021-11-24)


### Features

* **ConfigState:** implement less performance hungry state merge strategy ([eea9aef](https://github.com/Neunerlei/configuration/commit/eea9aef9eadfd96a313ef8958c696f2f25baf573))

### [2.5.1](https://github.com/Neunerlei/configuration/compare/v2.5.0...v2.5.1) (2021-11-24)


### Bug Fixes

* allow newer psr/container on PHP74 ([3c75ec4](https://github.com/Neunerlei/configuration/commit/3c75ec4ae7e1302befa7860cecdafecb788994ec))

## [2.5.0](https://github.com/Neunerlei/configuration/compare/v2.4.3...v2.5.0) (2021-11-24)


### Features

* **loader:** allow setting an initial state object when executing load() ([a4f09f0](https://github.com/Neunerlei/configuration/commit/a4f09f07090dd552cc2d3dca52ff5d7941c4b6d3))
* ensure $loader->load() method does not create a new state object when loading data from cache ([71c74d0](https://github.com/Neunerlei/configuration/commit/71c74d00f5169861614883fc8327635e219dc82e))


### Bug Fixes

* downgrade psr/container to 1.1.1 to keep PHP73 compatibility ([702b6eb](https://github.com/Neunerlei/configuration/commit/702b6eb99e072d97dab617e72e64541bf4505d1a))
* **ConfigState:** ensure watchers are triggered when using importFrom() ([89515f6](https://github.com/Neunerlei/configuration/commit/89515f6c3d9761d9e8db32b05a6ef52836d9a14d))
* **Loader:** ensure cached values are injected into the state correctly ([eda1c86](https://github.com/Neunerlei/configuration/commit/eda1c862c250786d8f6abf5abe6ef513897d85d4))

### [2.4.3](https://github.com/Neunerlei/configuration/compare/v2.4.2...v2.4.3) (2021-10-25)

### [2.4.2](https://github.com/Neunerlei/configuration/compare/v2.4.1...v2.4.2) (2021-10-25)


### Bug Fixes

* update dependencies ([3b2e54f](https://github.com/Neunerlei/configuration/commit/3b2e54ff768614fcea48ff4abf1620cb5c410dd5))

### [2.4.1](https://github.com/Neunerlei/configuration/compare/v2.4.0...v2.4.1) (2021-10-25)


### Bug Fixes

* **ConfigFilter:** fix typo in exception message ([af478b1](https://github.com/Neunerlei/configuration/commit/af478b18e6bd3ef932976013ec95684dd4cef04c))

## [2.4.0](https://github.com/Neunerlei/configuration/compare/v2.3.0...v2.4.0) (2021-05-05)


### Features

* **State:** execute $filter on initial state in LocallyCachedStatePropertyTrait ([21be170](https://github.com/Neunerlei/configuration/commit/21be170847fd82a851549880dcfced7e50d3fb6d))

## [2.3.0](https://github.com/Neunerlei/configuration/compare/v2.2.0...v2.3.0) (2021-02-15)


### Features

* **ConfigState:** implement importFrom() method ([4105517](https://github.com/Neunerlei/configuration/commit/41055171431bc9b457b5c8d26f57c7ae92df6156))

## [2.2.0](https://github.com/Neunerlei/configuration/compare/v2.1.0...v2.2.0) (2021-02-15)


### Features

* implement new BeforeStateCachingEvent ([0b814d8](https://github.com/Neunerlei/configuration/commit/0b814d8d996191d94f145036a0e1e962451a1087))

## [2.1.0](https://github.com/Neunerlei/configuration/compare/v2.0.1...v2.1.0) (2021-02-15)


### Features

* **ConfigState:** introduce new setter utils ([02214ad](https://github.com/Neunerlei/configuration/commit/02214adfd43f30a69a928a676be900579efe08a0))
* **LocallyCachedStatePropertyTrait:** introduce $filter parameter for registerCachedProperty() ([bebf6cd](https://github.com/Neunerlei/configuration/commit/bebf6cd51106d2ee2b9b0a0cf7aac06f0195cd83))

### [2.0.1](https://github.com/Neunerlei/configuration/compare/v2.0.0...v2.0.1) (2021-02-12)


### Bug Fixes

* update to arrays@3.2 fix version ([9021244](https://github.com/Neunerlei/configuration/commit/9021244f772afa7dbd11baa4e1606337dd08a63a))

## [2.0.0](https://github.com/Neunerlei/configuration/compare/v1.5.1...v2.0.0) (2021-02-12)


### ⚠ BREAKING CHANGES

* I now use Arrays@3.1 internally. This might break your
setup if you depend on it indirectly. I had to adjust the code a bit to
match the new version.

### Features

* update dependencies ([a85a5ef](https://github.com/Neunerlei/configuration/commit/a85a5eff40293bb5e7b66c50c015b410cd273920))

### [1.5.1](https://github.com/Neunerlei/configuration/compare/v1.5.0...v1.5.1) (2021-01-30)


### Bug Fixes

* **Finder:** make sure abstract classes are ignored correctly ([6d4df51](https://github.com/Neunerlei/configuration/commit/6d4df51b0f54fffe230da5047f62bba9ee9354d2))

## [1.5.0](https://github.com/Neunerlei/configuration/compare/v1.4.0...v1.5.0) (2021-01-30)


### Features

* **AbstractConfigDefinition:** add public getter methods for properties ([7be3a81](https://github.com/Neunerlei/configuration/commit/7be3a81c1bd2c8c85d78295252a588031073fd44))
* **AbstractConfigHandler:** implement setDefinition() ([7dc51c9](https://github.com/Neunerlei/configuration/commit/7dc51c963e116d13685cfdda05673bcbf6a81acd))


### Bug Fixes

* **ConfigDefinitionFilterEvent:** fix type annotation ([dbf9d80](https://github.com/Neunerlei/configuration/commit/dbf9d8056c4f9bbc9f6f9a194437417ec5f73aba))

## [1.4.0](https://github.com/Neunerlei/configuration/compare/v1.3.0...v1.4.0) (2020-09-04)


### Features

* **ConfigState:** make ConfigState watchable ([7da6f1c](https://github.com/Neunerlei/configuration/commit/7da6f1c24eecb388d96753718594771c467c39d8))

## [1.3.0](https://github.com/Neunerlei/configuration/compare/v1.2.0...v1.3.0) (2020-08-25)


### Features

* **ConfigState:** use Neunerlei/Arrays package for getter and setter operations ([5aa70c3](https://github.com/Neunerlei/configuration/commit/5aa70c3643698f93450980a0fda26b5c27efa030))

## [1.2.0](https://github.com/Neunerlei/configuration/compare/v1.1.5...v1.2.0) (2020-08-24)


### Features

* **FilteredHandlerFinder:** only apply the filter to the list of classes, not to manually registered handlers ([614a76d](https://github.com/Neunerlei/configuration/commit/614a76d81ba9d3b5f9d4fc1a7de086bab8cd0ef5))

### [1.1.5](https://github.com/Neunerlei/configuration/compare/v1.1.4...v1.1.5) (2020-08-20)

### [1.1.4](https://github.com/Neunerlei/configuration/compare/v1.1.3...v1.1.4) (2020-08-20)

### [1.1.3](https://github.com/Neunerlei/configuration/compare/v1.1.2...v1.1.3) (2020-08-20)

### [1.1.2](https://github.com/Neunerlei/configuration/compare/v1.1.1...v1.1.2) (2020-08-20)

### [1.1.1](https://github.com/Neunerlei/configuration/compare/v1.1.0...v1.1.1) (2020-08-20)

## 1.1.0 (2020-08-20)


### Features

* initial commit ([0d459e1](https://github.com/Neunerlei/configuration/commit/0d459e12d43e48a41435a727e0341d332f28c393))


### Bug Fixes

* **ConfigLoader:** make sure the configuration is always ordered alphabetically ([d48fce7](https://github.com/Neunerlei/configuration/commit/d48fce705fc094f743698d36c0c643b49ab16573))
* **HandlerLoader:** make sure the handlers are always ordered alphabetically ([02b60e1](https://github.com/Neunerlei/configuration/commit/02b60e1d175e9d1b2be6e2f05a5e17ede10e7723))
