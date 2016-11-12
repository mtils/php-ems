<?php

namespace Ems\Contracts\Geo;

interface Address
{
    /**
     * Returns the coutry name.
     *
     * @return string
     **/
    public function country();

    /**
     * Returns the country code according to ISO 3166-1 alpha-3.
     *
     * @return string
     **/
    public function countryCode();

    /**
     * Returns the state.  The state is considered the first subdivision below
     * country.
     *
     * @return string
     **/
    public function state();

    /**
     * Returns the county.  The county is considered the second subdivision
     * below country.
     *
     * @return string
     **/
    public function county();

    /**
     * Returns the city.
     *
     * @return string
     **/
    public function city();

    /**
     * Returns the district. The district is considered the subdivison below
     * city.
     *
     * @return string
     **/
    public function district();

    /**
     * Returns the street-level component of the address.
     *
     * This typically includes a street number and street name
     * but may also contain things like a unit number, a building
     * name, or anything else that might be used to
     * distinguish one address from another.
     *
     * @return string
     **/
    public function street();

    /**
     * Returns the post code.
     *
     * @return string
     **/
    public function postCode();
}
