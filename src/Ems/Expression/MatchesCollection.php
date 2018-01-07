<?php
/**
 *  * Created by mtils on 07.01.18 at 10:16.
 **/

namespace Ems\Expression;


use Ems\Core\Collections\OrderedList;

/**
 * Class MatcherCollection
 *
 * This class is used to mark the extracted data which was caused
 * by a KeyExpression. If you match against a relation the single values
 * of that relation have to be retrieved. Example:
 * WHERE categories.id = 12 will need to get all the id values of every
 * assigned category. These id values are held inside a MatcherCollection.
 *
 * @package Ems\Expression
 */
class MatchesCollection extends OrderedList
{
    //
}