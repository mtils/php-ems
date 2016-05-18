<?php

namespace Ems\Mail;

use ArrayObject;
use Mockery as m;
use Ems\TestCase;
use Ems\Contracts\Mail\Transport;
use Ems\Contracts\Mail\MailConfigProvider;
use Ems\Contracts\Mail\MessageComposer;
use Ems\Testing\LoggingCallable;
use Ems\Testing\Cheat;


class MailerTest extends TestCase
{

    public function test_implements_interface()
    {
        $this->assertInstanceOf('Ems\Contracts\Mail\Mailer', $this->newMailer());
    }

    public function test_message_creates_message()
    {
        $message = $this->mockMessage();
        $transport = $this->mockTransport();

        $mailer = $this->newMailer($transport);

        $transport->shouldReceive('newMessage')
                  ->andReturn($message)
                  ->atLeast()->times(1);
        $message->shouldReceive('setMailer')
                ->with($mailer)
                ->atLeast()->times(1);
        $message->shouldReceive('to')->never()
                ->shouldReceive('subject')->never()
                ->shouldReceive('body')->never();

        $this->assertSame($message, $mailer->message());
    }

    public function test_message_sets_to_subject_and_body()
    {

        $message = $this->mockMessage();
        $transport = $this->mockTransport();

        $mailer = $this->newMailer($transport);

        $transport->shouldReceive('newMessage')
                  ->andReturn($message)
                  ->atLeast()->times(1);

        $message->shouldReceive('setMailer')
                ->with($mailer)
                ->atLeast()->times(1);

        $message->shouldReceive('to')
                ->atLeast()->times(1)
                ->with('to')
                ->shouldReceive('subject')
                ->atLeast()->times(1)
                ->with('subject')
                ->shouldReceive('body')
                ->atLeast()->times(1)
                ->with('body');

        $this->assertSame($message, $mailer->message('to','subject','body'));

    }

    public function test_to_returns_other_instance()
    {
        $mailer = $this->newMailer();
        $replicated = $mailer->to('foo@bar.de');
        $this->assertNotEquals(spl_object_hash($mailer), spl_object_hash($replicated));
    }

    public function test_to_copies_listeners()
    {
        $mailer = $this->newMailer();
        $sendingListener = new LoggingCallable;
        $sentListener = new LoggingCallable;
        $mailer->beforeSending($sendingListener);
        $mailer->afterSent($sentListener);

        $replicated = $mailer->to('foo@bar.de');
        $this->assertNotEquals(spl_object_hash($mailer), spl_object_hash($replicated));
        $this->assertSame($sendingListener, Cheat::get($replicated, 'sendingListener'));
        $this->assertSame($sentListener, Cheat::get($replicated, 'sentListener'));
    }

    public function test_to_takes_strings()
    {
        $mailer = $this->newMailer();
        $replicated = $mailer->to('foo@bar.de');
        $this->assertEquals(['foo@bar.de'], Cheat::get($replicated, 'to'));

        $mailer = $this->newMailer();
        $replicated = $mailer->to('foo@bar.de','bar@foo.de');
        $this->assertEquals(['foo@bar.de','bar@foo.de'], Cheat::get($replicated, 'to'));

    }

    public function test_to_takes_array()
    {
        $mailer = $this->newMailer();
        $recipients = ['foo@bar.de','bar@foo.de'];
        $replicated = $mailer->to($recipients);
        $this->assertEquals($recipients, Cheat::get($replicated, 'to'));
    }

    public function test_to_takes_traversable()
    {
        $mailer = $this->newMailer();
        $recipientsArray = ['foo@bar.de','bar@foo.de'];
        $recipients = new ArrayObject($recipientsArray);
        $replicated = $mailer->to($recipients);
        $this->assertSame($recipients, Cheat::get($replicated, 'to'));
    }

    public function test_finalRecipients_returns_overwritten_to()
    {
        $mailer = $this->newMailer();
        $mailer->alwaysSendTo('me@local');
        $recipients = Cheat::call($mailer, 'finalRecipients', ['user@remote.de']);
        $this->assertEquals(['me@local'], $recipients);
    }

    public function test_to_copies_overwrittenTo()
    {
        $mailer = $this->newMailer();
        $mailer->alwaysSendTo('me@local');
        $replicated = $mailer->to('user@remote');
        $this->assertEquals(['me@local'], $replicated->overwrittenTo());
    }

    /**
     * @expectedException UnderflowException
     **/
    public function test_send_throws_exception_if_no_recipients_found()
    {
        $configProvider = $this->mockConfigProvider();
        $configProvider->shouldReceive('configFor');
        $mailer = $this->newMailer(null, $configProvider, null);
        $mailer->send('activation.store');
    }

    public function test_send_calls_configProvider_composer_and_transport_and_callable()
    {

        $transport = $this->mockTransport();
        $configProvider = $this->mockConfigProvider();
        $composer = $this->mockComposer();
        $callable = new LoggingCallable;
        $config = $this->mockConfig();
        $message = $this->mockMessage();
        $mailer = $this->newMailer($transport, $configProvider, $composer);
        $sendResult = new SendResult($transport);

        $resourceId = 'registrations.store';
        $data = ['a'=>'b'];
        $recipients = ['foo@bar.de'];

        $configProvider->shouldReceive('configFor')
                       ->with($resourceId, $data)
                       ->atLeast()->once()
                       ->andReturn($config);

        $transport->shouldReceive('newMessage')
                  ->once()
                  ->andReturn($message);

        $composer->shouldReceive('fill')
                 ->with($message, $config, $recipients[0], $data);

        $transport->shouldReceive('send')
                  ->with($message)
                  ->andReturn($sendResult);

        $result = $mailer->to($recipients)->send($resourceId, $data, $callable);

        $this->assertInstanceOf(SendResult::class, $result);
        $this->assertEquals(1, count($callable));
        $this->assertSame($message, $callable->arg(0));

    }

    public function test_send_creates_messages_per_recipient()
    {

        $transport = $this->mockTransport();
        $configProvider = $this->mockConfigProvider();
        $composer = $this->mockComposer();
        $callable = new LoggingCallable;
        $config = $this->mockConfig();
        $message = $this->mockMessage();
        $mailer = $this->newMailer($transport, $configProvider, $composer);
        $sendResult = new SendResult($transport);

        $resourceId = 'registrations.store';
        $data = ['a'=>'b'];
        $recipients = ['foo@bar.de', 'bar@foo.de', 'nobody@somewhere.de'];

        $configProvider->shouldReceive('configFor')
                       ->with($resourceId, $data)
                       ->atLeast()->once()
                       ->andReturn($config);

        $transport->shouldReceive('newMessage')
                  ->times(count($recipients))
                  ->andReturn($message);

        $composer->shouldReceive('fill')
                 ->times(count($recipients));

        $transport->shouldReceive('send')
                  ->with($message)
                  ->times(count($recipients))
                  ->andReturn($sendResult);

        $mailer->to($recipients)->send($resourceId, $data, $callable);

        $this->assertEquals(count($recipients), count($callable));
        $this->assertSame($message, $callable->arg(0));

    }

    protected function newMailer(Transport $transport=null, MailConfigProvider $configProvider=null,
                                MessageComposer $composer=null)
    {
        $transport = $transport ?: $this->mockTransport();
        $transport->shouldReceive('beforeSending')->shouldReceive('afterSent');
        $configProvider = $configProvider ?: $this->mockConfigProvider();
        $composer = $composer ?: $this->mockComposer();
        return new Mailer($transport, $configProvider, $composer);
    }

    protected function mockTransport()
    {
        return $this->mock('Ems\Contracts\Mail\Transport');
    }

    protected function mockConfigProvider()
    {
        return $this->mock('Ems\Contracts\Mail\MailConfigProvider');
    }

    protected function mockComposer()
    {
        return $this->mock('Ems\Contracts\Mail\MessageComposer');
    }

    protected function mockMessage()
    {
        return $this->mock('Ems\Contracts\Mail\Message');
    }

    protected function mockConfig()
    {
        return $this->mock('Ems\Contracts\Mail\MailConfig');
    }

}