<?php
/**
 *  * Created by mtils on 12.08.19 at 14:02.
 **/

namespace Ems\Http\Serializer;


use Ems\Contracts\Core\Serializer;
use Ems\Contracts\Core\Type;
use UnexpectedValueException;
use function http_build_query;
use function is_object;
use function parse_str;
use function urldecode;
use function urlencode;
use const PHP_QUERY_RFC1738;

class UrlEncodeSerializer implements Serializer
{
    /**
     * @var string
     **/
    protected $mimeType = 'application/x-www-form-urlencoded';

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function mimeType()
    {
        return $this->mimeType;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param array $options (optional)
     *
     * @return string
     **/
    public function serialize($value, array $options=[])
    {
        if (!is_array($value) && !is_object($value)) {
            return urlencode($value);
        }
        return http_build_query($value, '', '&', PHP_QUERY_RFC1738);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $string
     * @param array $options (optional)
     *
     * @return mixed
     **/
    public function deserialize($string, array $options=[])
    {
        if (!Type::isStringable($string)) {
            throw new UnexpectedValueException('I can only deserialize stringable objects not ' . Type::of($string));
        }

        $string = "$string";

        if (strpos($string, '=') === false) {
            return urldecode($string);
        }

        $result = [];

        parse_str($string, $result);

        return $result;

    }
}