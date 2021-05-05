# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

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
