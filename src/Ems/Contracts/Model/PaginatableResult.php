<?php

namespace Ems\Contracts\Model;

/**
 * A PaginatableResult allows to paginate the result (later).
 *
 * @example foreach (User::where('name', 'John')->paginate(2) as $user)
 **/
interface PaginatableResult extends Result, Paginatable
{

}
