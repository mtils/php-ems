<?php

namespace Ems\Contracts\Core;

/**
 * An Entity is an object with an id and a resource name
 * Therefore it is routable and mostly cacheable and locatable
 * inside an application.
 **/
interface Entity extends AppliesToResource, Identifiable
{
}
