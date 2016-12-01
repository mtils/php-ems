# Entity Management System

[![Build Status](https://travis-ci.org/mtils/php-ems.svg?branch=master)](https://travis-ci.org/mtils/php-ems)
[![Coverage Status](https://coveralls.io/repos/github/mtils/php-ems/badge.svg?branch=master)](https://coveralls.io/github/mtils/php-ems?branch=master)
[![Latest Stable Version](https://poser.pugx.org/ems/framework/v/stable)](https://packagist.org/packages/ems/framework)
[![Total Downloads](https://poser.pugx.org/ems/framework/downloads)](https://packagist.org/packages/ems/framework)
[![License](https://poser.pugx.org/ems/framework/license)](https://packagist.org/packages/ems/framework)


The entity management system is a collection of interfaces for common software development tasks. Its main focus is to provide architectural solutions by common interfaces and patterns to ensure a maximum maintainability in bigger software projects.

EMS is build to be used on top of other frameworks like laravel, symfony or zend. The most implementations have no framework dependency but EMS is not meant to be used alone.

EMS ensures that you rely as little as possible on any external library, even on EMS itself. One principle is that only interface methods in EMS are public and no additional methods are visible.
To ensure that, a lot of interfaces have planned hooks (like Cache::onAfter('invalidate', callable $do)) to ensure its extendability without the need for inheritance or code duplication.

## The state of its packages
Currently this package could be seen as in beta phase. Almost all of my customer applications are based on cmsable, which is currently ported to ems. So the code works in big and stable applications. (Big means with for example 2 Million users/month)
But the interfaces are currently changing. All features are developed in a predefined chain of actions:

1. Requirement specification
2. Technical Specification
3. Implementation
4. Release
5. Documentation.

So if a package is [documented](https://github.com/mtils/php-ems/wiki) it can be considered as stable.
