<?php
/**
 *  * Created by mtils on 03.10.19 at 06:56.
 **/

namespace Ems\Skeleton\Connection;


use Ems\Skeleton\Connection\StdOutputConnection;
use Ems\Core\Response;

use function is_array;
use function is_bool;
use function str_replace;
use function strip_tags;
use const PHP_EOL;

class ConsoleOutputConnection extends StdOutputConnection
{

    public const LINE_CONTENT_TYPE = 'text/x-console-lines';

    /**
     * @var array
     */
    private $tagStyles = [
        'info'      => '0;32;40',
        'mute'      => '1;30;40',
        'comment'   => '0;33;40',
        'warning'   => '1;33;40',
        'error'     => '1;37;41'
    ];

    /**
     * @var bool
     */
    private $formattedOutput = true;

    /**
     * @var array
     */
    private $search;

    /**
     * @var
     */
    private $replace;

    /**
     * Output a line. Replace any tags with console color styles.
     *
     * @param string $output
     * @param bool   $formatted (optional)
     * @param string $newLine (default: PHP_EOL)
     */
    public function line($output, $formatted=null, $newLine=PHP_EOL)
    {
        $formatted = is_bool($formatted) ? $formatted : $this->shouldFormatOutput();
        $output = $formatted ? $this->format($output) : $this->removeTags($output);
        $this->write($output . $newLine);
    }

    /**
     * Replace the tags inside the string with console color styles.
     *
     * @param string $output
     *
     * @return string
     */
    public function format($output)
    {
        return str_replace($this->search(), $this->replace(), $output);
    }

    /**
     * Get the console color style for a tag name.
     *
     * @param string $tag
     *
     * @return string
     */
    public function getTagStyle($tag)
    {
        return $this->tagStyles[$tag];
    }

    /**
     * Set a style (console color code) for a tag name
     *
     * @param string|array $tag
     * @param string       $style (optional)
     *
     * @return $this
     */
    public function setTagStyle($tag, $style=null)
    {
        if (!is_array($tag)) {
            $this->tagStyles[$tag] = $style;
            $this->search = null;
            $this->replace = null;
            return $this;
        }
        foreach ($tag as $name=>$style) {
            $this->setTagStyle($name, $style);
        }
        return $this;
    }

    /**
     * Returns true when the tags should be colored. (Otherwise they get removed)
     *
     * @return bool
     */
    public function shouldFormatOutput()
    {
        return $this->formattedOutput;
    }

    /**
     * @param $output
     * @param bool $lock
     * @return bool|mixed|void|null
     */
    public function write($output, bool $lock = false)
    {
        if (!$output instanceof Response) {
            return parent::write($output, $lock);
        }
        if ($output->contentType() != self::LINE_CONTENT_TYPE) {
            return parent::write($output, $lock);
        }
        $lines = explode(PHP_EOL, $output->payload());

        foreach ($lines as $line) {
            $this->line($line);
        }
    }


    /**
     * @param string $string
     *
     * @return string
     */
    protected function removeTags($string)
    {
        return strip_tags($string);
    }

    /**
     * @return array
     */
    protected function search()
    {
        if (is_array($this->search)) {
            return $this->search;
        }
        $this->search = [];
        foreach ($this->tagStyles as $tag=>$style) {
            $this->search[] = "<$tag>";
            $this->search[] = "</$tag>";
        }
        return $this->search;
    }

    /**
     * @return array
     */
    protected function replace()
    {
        if (is_array($this->replace)) {
            return $this->replace;
        }
        $this->replace = [];
        foreach ($this->tagStyles as $tag=>$style) {
            $this->replace[] = "\e[$style" . 'm';
            $this->replace[] = "\e[0m";
        }
        return $this->replace;
    }
}