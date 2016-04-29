<?php 

namespace Ems\Mail\Laravel;

use Ems\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Mail\Mailer as LaravelMailer;

class Mailer implements MailerContract
{

    /**
     * @var \Illuminate\Contracts\Mail\Mailer
     **/
    protected $laravelMailer;

    /**
     * @var array
     **/
    protected $to = [];

    /**
     * @var array
     **/
    protected $overwrittenTo = [];

    /**
     * @var callable
     **/
    protected $dataProcessor;

    /**
     * @var callable
     **/
    protected $viewNameProcessor;

    /**
     * @param \Illuminate\Contracts\Mail\Mailer $laravelMailer
     **/
    public function __construct(LaravelMailer $laravelMailer)
    {

        $this->laravelMailer = $laravelMailer;

        $this->dataProcessor = function($data){};

        $this->viewNameProcessor = function($viewName){};

    }

    /**
     * {@inheritdoc}
     * 
     * @param mixed $recipient string|array for more than one
     * @return self
     **/
    public function to($recipient)
    {
        $to = func_num_args() > 1 ? func_get_args() : (array)$recipient;
        return $this->replicateForFluidApi($to);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $view The template name
     * @param array $data The view vars
     * @param callable $callback (optional) A closure to modify the mail before send
     **/
    public function plain($view, array $data, $callback=null)
    {
        return $this->send(['text'=>$view], $data, $callback);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $view The (blade) template name
     * @param array $data The view vars
     * @param callable $callback (optional) A closure to modify the mail before send
     **/
    public function send($view, array $data, $callback=null)
    {

        $recipients = $this->finalRecipients($this->to);

        $view = $this->finalView($view);

        $data = $this->parseTexts($this->finalData($data));

        $messageBuilder = $this->createBuilder($recipients, $data, $callback);

        return $this->laravelMailer->send($view, $data, $messageBuilder->builder());

    }

    /**
     * @return array
     **/
    public function overwrittenTo()
    {
        return $this->overwrittenTo;
    }

    /**
     * @param string|array $to
     * @return self
     **/
    public function overwriteTo($to)
    {
        $this->overwrittenTo = func_num_args() > 1 ? func_get_args() : (array)$to;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $processor
     * @return self
     **/
    public function processDataWith(callable $processor)
    {
        $this->dataProcessor = $processor;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $processor
     * @return self
     **/
    public function processViewNameWith(callable $processor)
    {
        $this->viewNameProcessor = $processor;
        return $this;
    }

    public function _setTo($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * Returns only the overwritten recipients or if non set the passed ones
     *
     * @param array $passedTo
     * @return array
     **/
    protected function finalRecipients(array $passedTo)
    {
        if ($this->overwrittenTo) {
            return $overwrittenTo;
        }

        return $passedTo;

    }

    /**
     * Ask the view processor for a new view name or return the passed
     *
     * @param string|array $passedView
     * @return string|array
     **/
    protected function finalView($passedView)
    {
        if ($overWritten = call_user_func($this->viewNameProcessor, $passedView)) {
            return $overWritten;
        }
        return $passedView;
    }

    /**
     * Asks the data processor to process the data or return the passed
     *
     * @param array $passedData
     * @return array
     **/
    protected function finalData(array $passedData)
    {
        if ($overWritten = call_user_func($this->dataProcessor, $passedData)) {
            return $overWritten;
        }
        return $passedData;
    }

    /**
     * Create the pseudo closure creator
     *
     * @param array $recipients
     * @param array $data
     * @param callable|null $callback
     * @return \Cmsable\Mail\MessageBuilder
     **/
    protected function createBuilder(array $recipients, $data, $callback)
    {
        return new MessageBuilder($recipients, $data, $callback);
    }

    protected function replicateForFluidApi(array $to)
    {
        $copy = new static($this->laravelMailer);

        return $copy->_setTo($to)->overwriteTo($this->overwrittenTo);
    }

}