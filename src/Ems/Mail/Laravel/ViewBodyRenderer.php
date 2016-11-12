<?php

namespace Ems\Mail\Laravel;

use Illuminate\Contracts\View\Factory;
use Ems\Contracts\Mail\BodyRenderer;
use Ems\Contracts\Mail\MailConfig;

class ViewBodyRenderer implements BodyRenderer
{
    /**
     * Try to render plain text in mails with templates containing this suffix.
     *
     * @var string
     **/
    public $plainSuffix = '.plain';

    /**
     * @var \Illuminate\Contracts\View\Factory
     **/
    protected $views;

    /**
     * @var callable
     **/
    protected $htmlPlainConverter;

    /**
     * @param \Illuminate\Contracts\View\Factory $views
     **/
    public function __construct(Factory $views)
    {
        $this->views = $views;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Mail\MailConfig
     * @param aray $data
     *
     * @return string
     **/
    public function html(MailConfig $config, array $data)
    {
        return $this->views->make($config->template(), $data)->render();
    }

    /**
     * {@inheritdoc}
     *
     * This class looks for a special plain text template. If non exists it
     * checks for a assigned plaintext converter. If non found it returns an
     * empty string
     *
     * @param \Ems\Contracts\Mail\MailConfig
     * @param aray $data
     *
     * @return string
     **/
    public function plainText(MailConfig $config, array $data)
    {
        $plainTemplate = $config->template().$this->plainSuffix;

        if ($this->views->exists($plainTemplate)) {
            return $this->views->make($plainTemplate, $data)->render();
        }

        if (!$this->htmlPlainConverter) {
            return '';
        }

        return call_user_func($this->htmlPlainConverter, $this->html($config, $data),  $data);
    }

    /**
     * Assign a html to plaintext converter. It will be called with the html
     * contents of the mail and the data.
     *
     * @param callable $converter
     *
     * @return self
     **/
    public function convertToPlainTextBy(callable $converter)
    {
        $this->htmlPlainConverter = $converter;

        return $this;
    }
}
