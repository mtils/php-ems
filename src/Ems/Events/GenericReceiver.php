<?php
/**
 * Created by PhpStorm.
 * User: michi
 * Date: 11.11.17
 * Time: 10:12
 */

namespace Ems\Events;


use Ems\Contracts\Events\Receiver;
use Ems\Core\NamedObject;
use Ems\Contracts\Core\Url as UrlContract;

class GenericReceiver extends NamedObject implements Receiver
{

    /**
     * @var string
     */
    protected $signalName = '';

    /**
     * @var UrlContract
     */
    protected $url;

    /**
     * GenericReceiver constructor.
     *
     * @param string      $signalName
     * @param UrlContract $url
     */
    public function __construct($signalName, UrlContract $url)
    {
        parent::__construct(
            $this->generateId($signalName, $url),
            $this->generateName($signalName, $url),
            'signal-receivers'
        );

        $this->signalName = $signalName;
        $this->url = $url;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getSignalName()
    {
        return $this->signalName;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Generate unique id for this receiver.
     *
     * @param string      $signalName
     * @param UrlContract $url
     *
     * @return string
     */
    protected function generateId($signalName, UrlContract $url)
    {
        $signalPart = preg_replace('/[^a-zA-Z0-9_]/u', '_', $signalName);
        $urlPart = preg_replace('/[^a-zA-Z0-9_]/u', '_', (string) $url);

        return "$signalPart--$urlPart";
    }

    /**
     * Generate a nice name for this receiver.
     *
     * @param string      $signalName
     * @param UrlContract $url
     *
     * @return string
     */
    protected function generateName($signalName, UrlContract $url)
    {
        return "Receiver of '$signalName' at $url";
    }
}