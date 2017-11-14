<?php

namespace Ems\Contracts\Core;

/**
 * An expression is a passthru object, which will be interpreted as
 * "Just pass the contained string and dont parse it". This is used in
 * database queries (something like DB::raw() in laravel) or HtmlString
 * to not decode it and so on
 **/
interface Expression extends Stringable
{
    //
}
