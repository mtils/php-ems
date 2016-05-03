<?php 

namespace Ems\Mail\Laravel;

use UnexpectedValueException;
use Traversable;

use Ems\Contracts\Mail\Mailer as MailerContract;
use Ems\Contracts\Mail\MailConfigBuilder;
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
     * @var \Ems\Contracts\Mail\MailConfigBuilder
     **/
    protected $configBuilder;

    /**
     * @param \Illuminate\Contracts\Mail\Mailer $laravelMailer
     * @param \Ems\Contracts\Mail\MailConfigBuilder $configBuilder
     **/
    public function __construct(LaravelMailer $laravelMailer, MailConfigBuilder $configBuilder)
    {
        $this->laravelMailer = $laravelMailer;
        $this->configBuilder = $configBuilder;
    }

    /**
     * {@inheritdoc}
     * 
     * @param mixed $recipient string|array for more than one
     * @return self
     **/
    public function to($recipient)
    {
        return $this->replicateForFluidApi($this->parseRecipients(func_get_args()));
    }

    /**
     * {@inheritdoc}
     *
     * @param string $resourceId A resource id like registrations.activate
     * @param array $data (optional) The view vars (subject, body, ...)
     * @param callable $callback (optional) A closure to modify the mail(s) before send
     **/
    public function plain($resourceId, array $data=[], $callback=null)
    {
        return $this->send(['text'=>$resourceId], $data, $callback);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $resourceId A resource id like registrations.activate
     * @param array $data (optional) The view vars (subject, body, ...)
     * @param callable $callback (optional) A closure to modify the mail(s) before send
     **/
    public function send($resourceId, array $data, $callback=null)
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
     * @param array|\Traversable $passedTo
     * @return array
     **/
    protected function finalRecipients($passedTo)
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

    /**
     * Parses the recipients to something traversable
     *
     * @param array $toArgs
     * @return \Traversable
     **/
    protected function parseRecipients($toArgs)
    {
        if (count($toArgs) > 1) {
            return $toArgs;
        }

        if (is_array($toArgs[0])) {
            return $toArgs[0];
        }

        if (is_string($toArgs[0])) {
            return (array)$toArgs[0];
        }

        if ($toArgs[0] instanceof Traversable) {
            return $toArgs[0];
        }

        throw new UnexpectedValueException('Unparsable $to parameter');
    }

    protected function replicateForFluidApi($to)
    {
        $copy = new static($this->laravelMailer);

        return $copy->_setTo($to)->overwriteTo($this->overwrittenTo);
    }

}