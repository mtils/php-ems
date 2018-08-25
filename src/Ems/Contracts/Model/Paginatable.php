<?php
/**
 *  * Created by mtils on 24.08.18 at 16:39.
 **/

namespace Ems\Contracts\Model;

/**
 * A PaginatableResult allows to paginate the result (later).
 *
 * @example foreach (User::where('name', 'John')->paginate(2) as $user)
 **/
interface Paginatable
{
    /**
     * Paginate the result. Return whatever paginator you use.
     * The paginator should be \Traversable.
     *
     * @param int $page    (optional)
     * @param int $perPage (optional)
     *
     * @return \Traversable|array A paginator instance or just an array
     **/
    public function paginate($page = 1, $perPage = 15);
}