<?php 

namespace Ems\Mail;

use UnexpectedValueException;
use Traversable;
use UnderflowException;
use InvalidArgumentException;
use Countable;

use Ems\Contracts\Mail\Mailer as MailerContract;
use Ems\Contracts\Mail\MailConfigProvider;
use Ems\Contracts\Mail\MessageComposer;
use Ems\Contracts\Mail\Transport;
use Ems\Contracts\Mail\Message as MessageContract;
use Ems\Contracts\Mail\SendResult as ResultContract;
use Ems\Mail\SendResult;


class Mailer implements MailerContract
{

    /**
     * @var \Ems\Contracts\Mail\Transport
     **/
    protected $transport;

    /**
     * @var \Ems\Contracts\Mail\MailConfigBuilder
     **/
    protected $configProvider;

    /**
     * @var \Ems\Contracts\Mail\MailComposer
     **/
    protected $composer;

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
    protected $sendingListener;

    /**
     * @var callable
     **/
    protected $sentListener;

    /**
     * @param \Ems\Contracts\Mail\Transport $transport
     * @param \Ems\Contracts\Mail\MailConfigProvider $configProvider
     **/
    public function __construct(Transport $transport, MailConfigProvider $configProvider,
                                MessageComposer $composer)
    {
        $this->transport = $transport;
        $this->configProvider = $configProvider;
        $this->composer = $composer;

        $this->sendingListener = function($message){};
        $this->sentListener = function($message){};

        $this->transport->beforeSending(function(MessageContract $message){
            call_user_func($this->sendingListener, $message);
        });

        $this->transport->afterSent(function(MessageContract $message){
            call_user_func($this->sentListener, $message);
        });
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
     * @param string $to The recipient, email or something handled by ReciepientCaster
     * @param string $subject
     * @param string $body The text body
     * @return \Ems\Contracts\Mail\Message
     **/
    public function message($to='', $subject='', $body='')
    {

        $message = $this->transport->newMessage();

        if ($to) {
            $message->to($to);
        }
        if ($subject) {
            $message->subject($subject);
        }
        if ($body) {
            $message->body($body);
        }

        $message->setMailer($this);

        return $message;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $resourceId A resource id like registrations.activate
     * @param array $data (optional) The view vars (subject, body, ...)
     * @param callable $callback (optional) A closure to modify the mail(s) before send
     * @return \Ems\Contracts\Mail\SendResult
     **/
    public function send($resourceId, array $data=[], $callback=null)
    {

        $recipients = $this->finalRecipients($this->to);

        $config = $this->configProvider->configFor($resourceId, $data);

        $result = $this->newSendResult($this->transport);

        foreach ($recipients as $recipient) {

            $message = $this->transport->newMessage();

            $message->setMailer($this);
            $message->setConfiguration($config);

            $this->composer->fill($message, $recipient, $data);

            if (is_callable($callback)) {
                call_user_func($callback, $message);
            }

            $transportResult = $this->sendMessage($message);
            $this->mergeTransportResult($transportResult, $result);
        }

        if (!isset($message)) {
            throw new UnderflowException('No recipients found. Pass them via Mailer::to($reciepient)');
        }

        return $result;

    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Mail $message
     * @return \Ems\Contracts\Mail\SendResult
     **/
    public function sendMessage(MessageContract $message)
    {
        call_user_func($this->sendingListener, $message);
        return $this->transport->send($message);
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function beforeSending(callable $listener)
    {
        $this->sendingListener = $listener;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     * @return self
     **/
    public function afterSent(callable $listener)
    {
        $this->sentListener = $listener;
        return $this;
    }

    /**
     * @return array
     **/
    public function overwrittenTo()
    {
        return $this->overwrittenTo;
    }

    /**
     * Always send all mails only to the passed address(es)
     * This is only for testing purposes to not send mails to the outside
     * while developing. One call of this method will affect all send()
     * calls
     *
     * @param string|array $to
     * @return self
     **/
    public function alwaysSendTo($to)
    {
        $this->overwrittenTo = func_num_args() > 1 ? func_get_args() : (array)$to;
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
            return $this->overwrittenTo;
        }

        return $passedTo;

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

    /**
     * Replicates the mailer for the fluid to() api.
     *
     * @param mixed $to
     * @return self
     **/
    protected function replicateForFluidApi($to)
    {
        $copy = new static($this->transport, $this->configProvider, $this->composer);

        $copy->beforeSending($this->sendingListener);
        $copy->afterSent($this->sentListener);

        return $copy->_setTo($to)->alwaysSendTo($this->overwrittenTo);
    }

    /**
     * Creates a new SendResult. Overwrite this method for another class
     *
     * @return \Ems\Contracts\Mail\SendResult
     **/
    protected function newSendResult(Transport $transport)
    {
        return new SendResult($transport);
    }

    /**
     * Merges the result of a transport send operation into a global one
     *
     * @param \Ems\Contracts\Mail\SendResult $transportResult
     * @param \Ems\Contracts\Mail\SendResult $mailerResult
     **/
    protected function mergeTransportResult(ResultContract $transportResult, ResultContract $mailerResult)
    {
        if (!$mailerResult instanceof SendResult) {
            throw new InvalidArgumentException('mergeTransportResult can only merge Ems\Mail\SendResult');
        }

        $mailerResult->addFailedRecipient($transportResult->failures());
        $mailerResult->increment(count($transportResult));
    }

}