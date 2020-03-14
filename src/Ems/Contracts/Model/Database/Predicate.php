<?php
/**
 *  * Created by mtils on 23.12.19 at 06:26.
 **/

namespace Ems\Contracts\Model\Database;


use Ems\Contracts\Core\Stringable;
use function func_num_args;

/**
 * Class Predicate
 *
 * The predicate stores an sql condition. It is not a Expression\Condition
 * to have less dependencies and keep SQL stuff as fast and simple
 * as it can.
 * It is assumed (by this class and others in this namespace) that $left is
 * a column (and should be escaped like this).
 * If you want to pass something unescaped you have to wrap it into an Expression.
 *
 * This right is the opposite. It is assumed that right is a value and will be
 * assumed as a prepared parameter. To directly write into the string use an
 * Expression.
 *
 * @package Ems\Contracts\Model\Database
 *
 * @property-read string|Stringable left       Automatically evaluates to a column/alias
 * @property-read string            operator
 * @property-read mixed             right      Automatically evaluates to prepared parameters
 * @property-read boolean           rightIsKey Is the right operand by default a key? Normally not, only in JoinClause
 */
class Predicate
{

    /**
     * @var string|Stringable
     */
    protected $left = '';

    /**
     * @var string
     */
    protected $operator = '=';

    /**
     * @var mixed
     */
    protected $right;

    /**
     * @var boolean
     */
    protected $rightIsKey = false;

    public function __construct($left = '', $operatorOrRight = '', $right = null)
    {
        $numArgs = func_num_args();

        if ($numArgs === 0) {
            return;
        }

        if ($numArgs === 1) {
            $this->left = $left;
            $this->operator = '';
            return;
        }

        if ($numArgs === 2) {
            $this->left = $left;
            $this->right = $operatorOrRight;
            return;
        }

        $this->left = $left;
        $this->operator = $operatorOrRight;
        $this->right = $right;
    }

    /**
     * @param string $name
     *
     * @return Stringable|mixed|string
     */
    public function __get($name)
    {
        switch ($name) {
            case 'left':
                return $this->left;
            case 'operator':
                return $this->operator;
            case 'right':
                return $this->right;
            case 'rightIsKey':
                return $this->rightIsKey;
        }
        return null;
    }

    /**
     * @param bool $isKey (default:true)
     *
     * @return $this
     */
    public function rightIsKey($isKey = true)
    {
        $this->rightIsKey = $isKey;
        return $this;
    }
}