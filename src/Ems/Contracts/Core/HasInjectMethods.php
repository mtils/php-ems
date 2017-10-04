<?php

namespace Ems\Contracts\Core;

/**
 * This interface marks a class that it has methods to inject its dependencies.
 * It is empty so that you can choose your own injection names.
 *
 * @example public function injectValidator(Validator $validator)
 *
 * If you now resolve your class threw the container, the dependencies gets
 * automatically injected.
 *
 **/
interface HasInjectMethods
{
}
