<?php

/**
 *  * Created by mtils on 29.03.20 at 19:17.
 **/

namespace Ems\Contracts\Core;

/**
 * Interface DataRepository
 *
 * The DataRepository is the data layer between repositories and the storage
 * system. It works only with arrays. You should really use only your own repository
 * interfaces
 *
 * @package Ems\Contracts\Core
 */
interface DataRepository
{
    /**
     * Return the data for $id. Data usually includes the id.
     *
     * @param int|string $id
     *
     * @return array
     */
    public function get($id);

    /**
     * Create a record. Return the created data (that includes often generated
     * data or did manipulate the passed data).
     *
     * @param array $data
     *
     * @return array
     */
    public function create(array $data);

    /**
     * Update data. Return the updated data.
     *
     * @param array $data
     *
     * @return array
     */
    public function update(array $data);

    /**
     * Delete $data that is stored under $id.
     *
     * @param int|string $id
     *
     * @return bool
     */
    public function delete($id);
}
